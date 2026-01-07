<?php

declare(strict_types=1);

namespace App\Infrastructure\WsjtX;

use App\Infrastructure\Udp\Socket;
use App\Infrastructure\WsjtX\Messages\Builder;

final class Client
{
    /**
     * @var Socket
     */
    private Socket $socket;

    /**
     * @var Builder
     */
    private Builder $messageBuilder;

    /**
     * @var ?array<string, mixed>
     */
    private ?array $lastStatus = null;

    /**
     * @return void
     */
    public function __construct(Socket $socket, Builder $messageBuilder)
    {
        $this->socket = $socket;
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * Enable transmit mode (no-op; TX is enabled by sending messages).
     *
     * @return void
     */
    public function enableTx(): void
    {
        // this does nothing because WSJT-X doesn't have an "enable TX" command
        // you just send messages and it probably figure it out :)
    }

    /**
     * Disable transmit mode (halts current transmission).
     *
     * @return void
     */
    public function disableTx(): void
    {
        $this->haltTx();
    }

    /**
     * Halt current transmission.
     *
     * @return void
     */
    public function haltTx(): void
    {
        $packet = $this->messageBuilder->buildHaltTx();
        $this->socket->send($packet);
    }

    /**
     * Send a reply message to WSJT-X (triggers transmission).
     *
     * @return void
     */
    public function sendMessage(string $message): void
    {
        $packet = $this->messageBuilder->buildReply($message, false);
        $this->socket->send($packet);
    }

    /**
     * Toggle PTT over WSJT-X transport.
     *
     * @return void
     */
    public function sendPtt(bool $engage): void
    {
        if ($engage) {
            $this->haltTx();
        }
        // yes, engaging PTT halts TX because WSJT-X doesn't have direct PTT control
        // PTT is handled by CAT, not UDP. this is just a safety thing.
    }

    /**
     * Request status from WSJT-X (cached).
     *
     * @return array<string, mixed>
     */
    public function requestStatus(): array
    {
        if ($this->lastStatus !== null) {
            return $this->lastStatus;
        }

        return [
            'rig' => 'Unknown',
            'lastHeartbeat' => 0,
            'ptt' => false,
        ];
    }

    /**
     * Update cached status from parsed WSJT-X message.
     *
     * @param array<string, mixed> $status
     * @return void
     */
    public function updateStatus(array $status): void
    {
        $this->lastStatus = $status;
    }
}


