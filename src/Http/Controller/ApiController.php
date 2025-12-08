<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AutomationService;
use App\Domain\PTT\PttController;
use App\Domain\Qso\QsoLogEntry;
use App\Domain\Qso\QsoLogRepository;
use App\Http\JsonResponse;
use App\Infrastructure\WsjtX\WsjtXClient;
use InvalidArgumentException;

final class ApiController
{
    /**
     * @var WsjtXClient
     */
    private WsjtXClient $client;
    /**
     * @var PttController
     */
    private PttController $ptt;
    /**
     * @var QsoLogRepository
     */
    private QsoLogRepository $logs;
    /**
     * @var AutomationService
     */
    private AutomationService $automation;

    /**
     * @return void
     */
    public function __construct(
        WsjtXClient $client,
        PttController $ptt,
        QsoLogRepository $logs,
        AutomationService $automation
    ) {
        $this->client = $client;
        $this->ptt = $ptt;
        $this->logs = $logs;
        $this->automation = $automation;
    }

    /**
     * @return void
     */
    public function status(): void
    {
        JsonResponse::send([
            'status' => 'ok',
            'rig' => $this->client->requestStatus(),
            'pttEngaged' => $this->ptt->isEngaged(),
            'logCount' => count($this->logs->all()),
        ]);
    }

    /**
     * Toggle rig PTT from the API.
     *
     * @return void
     */
    public function togglePtt(): void
    {
        $engage = filter_var($_POST['engage'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $engage ? $this->ptt->engage() : $this->ptt->release();

        JsonResponse::send([
            'pttEngaged' => $this->ptt->isEngaged(),
        ]);
    }

    /**
     * Transmit a message and optionally log a QSO.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function transmit(): void
    {
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '') {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        $logEntry = null;
        if (isset($_POST['callSign'], $_POST['grid'], $_POST['mode'], $_POST['rstSent'], $_POST['rstRecv'])) {
            $logEntry = new QsoLogEntry(
                trim((string) $_POST['callSign']),
                trim((string) $_POST['grid']),
                trim((string) $_POST['mode']),
                trim((string) $_POST['rstSent']),
                trim((string) $_POST['rstRecv']),
                time(),
                isset($_POST['notes']) ? trim((string) $_POST['notes']) : null
            );
        }

        $result = $this->automation->transmit($message, $logEntry);
        JsonResponse::send($result);
    }

    /**
     * Return all QSO log entries.
     *
     * @return void
     */
    public function log(): void
    {
        JsonResponse::send([
            'entries' => array_map(
                static fn (QsoLogEntry $entry): array => $entry->toArray(),
                $this->logs->all()
            ),
        ]);
    }
}

