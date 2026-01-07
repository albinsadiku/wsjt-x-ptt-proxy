<?php

declare(strict_types=1);

namespace App\Infrastructure\Udp;

use RuntimeException;

/**
 * Low-level UDP sender for binary packets.
 */
final class Socket
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
     * Send a binary packet over UDP.
     *
     * @return void
     */
    public function send(string $binaryPacket): void
    {
        $socket = @stream_socket_client("udp://{$this->host}:{$this->port}", $errno, $errstr, 1);
        if (!$socket) {
            throw new RuntimeException("Cannot send to {$this->host}:{$this->port} - {$errstr} ({$errno})");
        }
        fwrite($socket, $binaryPacket);
        fclose($socket);
    }
}


