<?php

declare(strict_types=1);

namespace App\Domain\Automation;

use App\Domain\Qso\QsoLogEntry;
use App\Domain\Qso\QsoLogRepository;
use App\Infrastructure\Udp\Listener;
use App\Infrastructure\WsjtX\Client;
use App\Infrastructure\WsjtX\Messages\Parser;

final class AutomationController
{
    /**
     * @var bool
     */
    private bool $enabled = false;
    /**
     * @var string
     */
    private string $myCall;
    /**
     * @var string
     */
    private string $myGrid;
    /**
     * @var Client
     */
    private Client $client;
    /**
     * @var Parser
     */
    private Parser $parser;
    /**
     * @var Listener
     */
    private Listener $listener;
    /**
     * @var QsoLogRepository
     */
    private QsoLogRepository $logs;
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $recentDecodes = [];
    /**
     * @var ?string
     */
    private ?string $lastDxCall = null;
    /**
     * @var int
     */
    private int $qsoState = 0; // 0: idle, 1: CQ sent, 2: response sent, 3: RRR sent, 4: 73 sent

    public function __construct(
        Client $client,
        Parser $parser,
        Listener $listener,
        QsoLogRepository $logs,
        string $myCall = '',
        string $myGrid = ''
    ) {
        $this->client = $client;
        $this->parser = $parser;
        $this->listener = $listener;
        $this->logs = $logs;
        $this->myCall = $myCall;
        $this->myGrid = $myGrid;
    }

    /**
     * Enable automation.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->qsoState = 0;
    }

    /**
     * Disable automation.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->qsoState = 0;
    }

    /**
     * Check if automation is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Process incoming messages and handle automatic responses.
     *
     * @return array<string, mixed>|null Processed message or null
     */
    public function process(): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $packet = $this->listener->receive();
        if ($packet === null) {
            return null;
        }

        $message = $this->parser->parse($packet);
        if ($message === null) {
            return null;
        }

        if ($message['type'] === 'status') {
            $this->client->updateStatus($message);
        }

        if ($message['type'] === 'decode' && $message['new'] === true) {
            return $this->handleDecode($message);
        }

        if ($message['type'] === 'qso_logged') {
            $this->qsoState = 0;
            $this->lastDxCall = null;
        }

        return $message;
    }

    /**
     * Handle decode message and generate automatic response.
     *
     * @param array<string, mixed> $decode
     * @return array<string, mixed>
     */
    private function handleDecode(array $decode): array
    {
        $message = (string) ($decode['message'] ?? '');
        $mode = (string) ($decode['mode'] ?? '');

        // store recent decode
        $this->recentDecodes[] = $decode;
        if (count($this->recentDecodes) > 100) {
            array_shift($this->recentDecodes);
        }

        // extract callsign from message
        $dxCall = $this->extractCall($message);
        if ($dxCall === null) {
            return $decode;
        }

        // check if this is a call to us
        if ($this->isCallToUs($message, $dxCall)) {
            $response = $this->generateResponse($message, $dxCall, $mode);
            if ($response !== null) {
                $this->client->sendMessage($response);
                $this->lastDxCall = $dxCall;
                $this->qsoState++;
            }
        }

        return $decode;
    }

    /**
     * Extract callsign from FT8 message.
     *
     * @return string|null
     */
    private function extractCall(string $message): ?string
    {
        // regex magic: find any word that looks like a callsign
        // then ignore common ham words like CQ, DE, RRR, 73
        // not perfect by all means but it gets the job done (sometimes)
        if (preg_match('/\b([A-Z0-9]{3,15})\b/', $message, $matches)) {
            $call = $matches[1];
            if ($call !== 'CQ' && $call !== 'DE' && $call !== 'RRR' && $call !== '73') {
                return $call;
            }
        }
        return null;
    }

    /**
     * Check if message is a call to us.
     *
     * @return bool
     */
    private function isCallToUs(string $message, string $dxCall): bool
    {
        if ($this->myCall === '') {
            return false;
        }

        // check if message contains our callsign
        return stripos($message, $this->myCall) !== false;
    }

    /**
     * Generate automatic response based on message type and QSO state.
     *
     * @return string|null
     */
    private function generateResponse(string $message, string $dxCall, string $mode): ?string
    {
        // extract RST and grid
        $rst = $this->extractRst($message);
        $grid = $this->extractGrid($message);

        // qso sequence handling
        if ($this->qsoState === 0) {
            // first response: "DX_CALL MY_CALL RST GRID"
            $response = "{$dxCall} {$this->myCall} {$rst} {$this->myGrid}";
            return $response;
        } elseif ($this->qsoState === 1) {
            // second: "DX_CALL RRR"
            if (stripos($message, 'RRR') !== false) {
                return "{$dxCall} 73";
            }
            return "{$dxCall} RRR";
        } elseif ($this->qsoState === 2) {
            // final: "DX_CALL 73"
            return "{$dxCall} 73";
        }

        return null;
    }

    /**
     * Extract RST from message.
     *
     * @return string
     */
    private function extractRst(string $message): string
    {
        // look for RST pattern like +00, -05, etc.
        if (preg_match('/[+-]?\d{2}/', $message, $matches)) {
            return $matches[0];
        }
        return '+00';
    }

    /**
     * Extract grid square from message.
     *
     * @return string
     */
    private function extractGrid(string $message): string
    {
        // look for grid square pattern (4-6 characters)
        if (preg_match('/\b([A-R]{2}[0-9]{2}[A-X]{0,2})\b/', $message, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Get recent decode messages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentDecodes(): array
    {
        return $this->recentDecodes;
    }
}

