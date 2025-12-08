<?php

declare(strict_types=1);

namespace App\Infrastructure\WsjtX;

use RuntimeException;

final class WsjtXClient
{
    /**
     * @var string
     */
    private string $host;
    /**
     * @var int
     */
    private int $port;

    /**
     * @return void
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Toggle PTT over the WSJT-X transport.
     *
     * @return void
     * @todo Replace with actual CAT/PTT command or WSJT-X UDP message.
     */
    public function sendPtt(bool $engage): void
    {
        $this->mockSend('ptt', ['engage' => $engage]);
    }

    /**
     * Send a WSJT-X message payload.
     *
     * @return void
     */
    public function sendMessage(string $message): void
    {
        $this->mockSend('message', ['text' => $message]);
    }

    /**
     * @todo Replace with actual WSJT-X status request.
     * @return array<string, mixed>
     */
    public function requestStatus(): array
    {
        return [
            'rig' => 'Mocked rig',
            'lastHeartbeat' => time(),
            'ptt' => false,
        ];
    }

    /**
     * Mock transport used until a real WSJT-X link is wired.
     *
     * @return void
     */
    private function mockSend(string $channel, array $payload): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 0.5);
        if (!$socket) {
            throw new RuntimeException("Cannot reach WSJT-X at {$this->host}:{$this->port} - {$errstr}");
        }
        fwrite($socket, json_encode(['channel' => $channel, 'payload' => $payload], JSON_THROW_ON_ERROR));
        fclose($socket);
    }
}

