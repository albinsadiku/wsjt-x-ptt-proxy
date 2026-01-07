<?php

declare(strict_types=1);

namespace App\Infrastructure\Udp;

use RuntimeException;

/**
 * UDP listener for receiving binary packets.
 * @package App\Infrastructure\Udp
 */
final class Listener
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
     * @var resource|null
     */
    private $socket = null;

    /**
     * @return void
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Receive one UDP packet (non-blocking).
     *
     * @return string|null Binary packet or null if none available
     * @throws RuntimeException
     */
    public function receive(): ?string
    {
        if ($this->socket === null) {
            $address = "udp://{$this->host}:{$this->port}";
            $socket = @stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
            if (!$socket) {
                throw new RuntimeException("Failed to bind UDP socket at {$address}: {$errstr} ({$errno})");
            }
            stream_set_blocking($socket, false);
            $this->socket = $socket;
        }

        $packet = @stream_socket_recvfrom($this->socket, 4096);
        if ($packet === false || $packet === '') {
            return null;
        }

        return $packet;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}


