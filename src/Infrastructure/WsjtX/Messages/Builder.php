<?php

declare(strict_types=1);

namespace App\Infrastructure\WsjtX\Messages;


final class Builder
{
    /**
     * WSJT-X protocol magic number (big-endian).
     */
    private const MAGIC = 0xadbccbda;

    /**
     * Schema version 3.
     */
    private const SCHEMA = 3;

    /**
     * Message type: Reply (type 3).
     */
    private const TYPE_REPLY = 3;

    /**
     * Message type: Halt Tx (type 4).
     */
    private const TYPE_HALT_TX = 4;

    /**
     * Build a Reply message (type 3).
     *
     * @return string Binary packet
     */
    public function buildReply(string $message, bool $lowPriority = false): string
    {
        $packet = pack('N', self::MAGIC);
        $packet .= pack('N', self::SCHEMA);
        $packet .= pack('C', self::TYPE_REPLY);
        $packet .= pack('N', 0); // Message ID
        $packet .= $this->writeUtf8String($message);
        $packet .= pack('C', $lowPriority ? 1 : 0);

        return $packet;
    }

    /**
     * Build a Halt Tx message (type 4).
     *
     * @return string Binary packet
     */
    public function buildHaltTx(): string
    {
        $packet = pack('N', self::MAGIC);
        $packet .= pack('N', self::SCHEMA);
        $packet .= pack('C', self::TYPE_HALT_TX);
        $packet .= pack('N', 0); // Message ID

        return $packet;
    }

    /**
     * Write a UTF-8 string in QDataStream format (32-bit length + UTF-8 bytes).
     *
     * @return string
     */
    private function writeUtf8String(string $string): string
    {
        $bytes = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return pack('N', strlen($bytes)) . $bytes;
    }
}


