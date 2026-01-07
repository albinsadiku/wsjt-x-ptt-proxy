<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\PTT\PttController;
use App\Domain\Qso\QsoLogEntry;
use App\Domain\Qso\QsoLogRepository;
use App\Infrastructure\WsjtX\Client;

final class AutomationService
{
    /**
     * @var Client
     */
    private Client $client;
    /**
     * @var PttController
     */
    private PttController $pttController;
    /**
     * @var QsoLogRepository
     */
    private QsoLogRepository $logs;

    /**
     * @return void
     */
    public function __construct(
        Client $client,
        PttController $pttController,
        QsoLogRepository $logs
    ) {
        $this->client = $client;
        $this->pttController = $pttController;
        $this->logs = $logs;
    }

    /**
     * Transmit a message and log a QSO entry.
     *
     * @return array<string, mixed>
     */
    public function transmit(string $message, ?QsoLogEntry $logEntry = null): array
    {
        $this->pttController->engage();
        $this->client->sendMessage($message);

        if ($logEntry) {
            $this->logs->store($logEntry);
        }

        $this->pttController->release();

        return [
            'ok' => true,
            'ptt' => $this->pttController->isEngaged(),
            'logged' => (bool) $logEntry,
        ];
    }
}

