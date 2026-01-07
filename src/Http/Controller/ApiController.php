<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AutomationService;
use App\Domain\Automation\AutomationController;
use App\Domain\PTT\PttController;
use App\Domain\Qso\QsoLogEntry;
use App\Domain\Qso\QsoLogRepository;
use App\Http\JsonResponse;
use App\Infrastructure\WsjtX\Client;
use InvalidArgumentException;

final class ApiController
{
    /**
     * @var Client
     */
    private Client $client;
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
     * @var AutomationController
     */
    private AutomationController $automationController;

    /**
     * @return void
     */
    public function __construct(
        Client $client,
        PttController $ptt,
        QsoLogRepository $logs,
        AutomationService $automation,
        AutomationController $automationController
    ) {
        $this->client = $client;
        $this->ptt = $ptt;
        $this->logs = $logs;
        $this->automation = $automation;
        $this->automationController = $automationController;
    }

    /**
     * @return void
     */
    public function status(): void
    {
        $this->automationController->process();

        JsonResponse::send([
            'status' => 'ok',
            'rig' => $this->client->requestStatus(),
            'pttEngaged' => $this->ptt->isEngaged(),
            'logCount' => count($this->logs->all()),
            'automationEnabled' => $this->automationController->isEnabled(),
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

    /**
     * Enable or disable automation.
     *
     * @return void
     */
    public function toggleAutomation(): void
    {
        $enable = filter_var($_POST['enable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $enable ? $this->automationController->enable() : $this->automationController->disable();

        JsonResponse::send([
            'automationEnabled' => $this->automationController->isEnabled(),
        ]);
    }

    /**
     * Get automation status and recent decodes.
     *
     * @return void
     */
    public function automationStatus(): void
    {
        // process any pending messages
        $processed = $this->automationController->process();

        JsonResponse::send([
            'enabled' => $this->automationController->isEnabled(),
            'recentDecodes' => array_slice($this->automationController->getRecentDecodes(), -10),
            'lastProcessed' => $processed,
        ]);
    }
}

