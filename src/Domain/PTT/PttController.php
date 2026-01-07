<?php

declare(strict_types=1);

namespace App\Domain\PTT;

use App\Infrastructure\WsjtX\Client;

final class PttController
{
    /**
     * @var Client
     */
    private Client $client;
    /**
     * @var bool
     */
    private bool $engaged = false;

    /**
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Engage PTT on the connected rig.
     *
     * @return void
     */
    public function engage(): void
    {
        $this->client->sendPtt(true);
        $this->engaged = true;
    }

    /**
     * Release PTT.
     *
     * @return void
     */
    public function release(): void
    {
        $this->client->sendPtt(false);
        $this->engaged = false;
    }

    /**
     * @return bool
     */
    public function isEngaged(): bool
    {
        return $this->engaged;
    }
}

