<?php

declare(strict_types=1);

namespace App\Infrastructure\WsjtX\Messages;

final class Parser
{
    /**
     * WSJT-X protocol magic number (big-endian).
     */
    private const MAGIC = 0xadbccbda;

    /**
     * Minimum supported schema version.
     */
    private const MIN_SCHEMA = 2;

    /**
     * Maximum supported schema version.
     */
    private const MAX_SCHEMA = 3;

    /**
     * Parse a WSJT-X UDP packet into a message array.
     *
     * @return array<string, mixed>|null
     */
    public function parse(string $packet): ?array
    {
        if (strlen($packet) < 9) {
            return null;
        }

        $header = unpack('Nmagic/Nschema/Ctype', substr($packet, 0, 9));
        if ($header === false || $header['magic'] !== self::MAGIC) {
            return null;
        }

        $schema = $header['schema'];
        if ($schema < self::MIN_SCHEMA || $schema > self::MAX_SCHEMA) {
            return null;
        }

        $type = $header['type'];
        $offset = 9;

        // message types:
        // 0  heartbeat     - connectivity/version info (presence ping)
        // 1  status        - rig/app state snapshot (freq, mode, TX/RX, config)
        // 2  decode        - one decoded message (text, SNR, timing, flags)
        // 5  qso logged    - notification that WSJT-X logged a QSO
        // 6  close         - WSJT-X is closing; allow cleanup
        // 10 logged adif   - ADIF record emitted by WSJT-X
        return match ($type) {
            0 => $this->parseHeartbeat($packet, $offset, $schema),
            1 => $this->parseStatus($packet, $offset, $schema),
            2 => $this->parseDecode($packet, $offset, $schema),
            5 => $this->parseQsoLogged($packet, $offset, $schema),
            6 => $this->parseClose($packet, $offset, $schema),
            10 => $this->parseLoggedAdif($packet, $offset, $schema),
            default => null,
        };
    }

    /**
     * Parse Heartbeat message (type 0).
     * Presence/keepalive and version negotiation.
     *
     * @return array<string, mixed>
     */
    private function parseHeartbeat(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));
        $offset += 4;
        $maxSchema = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $version = $this->readUtf8String($packet, $offset);
        $revision = $this->readUtf8String($packet, $offset);

        return [
            'type' => 'heartbeat',
            'id' => $result['id'] ?? 0,
            'maxSchema' => $maxSchema,
            'version' => $version,
            'revision' => $revision,
        ];
    }

    /**
     * Parse Status message (type 1).
     * Snapshot of rig/app state (freq/mode/TX, station/DX, config).
     *
     * @return array<string, mixed>
     */
    private function parseStatus(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));
        $offset += 4;
        $dialFreq = unpack('Q', substr($packet, $offset, 8))[1] ?? 0;
        $offset += 8;
        $mode = $this->readUtf8String($packet, $offset);
        $dxCall = $this->readUtf8String($packet, $offset);
        $report = $this->readUtf8String($packet, $offset);
        $txMode = $this->readUtf8String($packet, $offset);
        $txEnabled = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $transmitting = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $decoding = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $rxDF = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $txDF = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $deCall = $this->readUtf8String($packet, $offset);
        $deGrid = $this->readUtf8String($packet, $offset);
        $dxGrid = $this->readUtf8String($packet, $offset);
        $txWatchdog = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $subMode = $this->readUtf8String($packet, $offset);
        $fastMode = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $specialOpMode = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $frequencyTolerance = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $trPeriod = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $configurationName = $this->readUtf8String($packet, $offset);
        $txMessage = $this->readUtf8String($packet, $offset);

        return [
            'type' => 'status',
            'id' => $result['id'] ?? 0,
            'dialFreq' => $dialFreq,
            'mode' => $mode,
            'dxCall' => $dxCall,
            'report' => $report,
            'txMode' => $txMode,
            'txEnabled' => (bool) $txEnabled,
            'transmitting' => (bool) $transmitting,
            'decoding' => (bool) $decoding,
            'rxDF' => $rxDF,
            'txDF' => $txDF,
            'deCall' => $deCall,
            'deGrid' => $deGrid,
            'dxGrid' => $dxGrid,
            'txWatchdog' => (bool) $txWatchdog,
            'subMode' => $subMode,
            'fastMode' => (bool) $fastMode,
            'specialOpMode' => $specialOpMode,
            'frequencyTolerance' => $frequencyTolerance,
            'trPeriod' => $trPeriod,
            'configurationName' => $configurationName,
            'txMessage' => $txMessage,
        ];
    }

    /**
     * Parse Decode message (type 2).
     * One decoded line with timing/frequency offsets and quality flags.
     *
     * @return array<string, mixed>
     */
    private function parseDecode(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));
        $offset += 4;
        $new = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $time = unpack('Q', substr($packet, $offset, 8))[1] ?? 0;
        $offset += 8;
        $snr = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $deltaTime = unpack('d', substr($packet, $offset, 8))[1] ?? 0.0;
        $offset += 8;
        $deltaFreq = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;
        $mode = $this->readUtf8String($packet, $offset);
        $message = $this->readUtf8String($packet, $offset);
        $lowConfidence = unpack('C', substr($packet, $offset, 1))[1] ?? 0;
        $offset += 1;
        $offAir = unpack('C', substr($packet, $offset, 1))[1] ?? 0;

        return [
            'type' => 'decode',
            'id' => $result['id'] ?? 0,
            'new' => (bool) $new,
            'time' => $time,
            'snr' => $snr,
            'deltaTime' => $deltaTime,
            'deltaFreq' => $deltaFreq,
            'mode' => $mode,
            'message' => $message,
            'lowConfidence' => (bool) $lowConfidence,
            'offAir' => (bool) $offAir,
        ];
    }

    /**
     * Parse QSO Logged message (type 5).
     * WSJT-X logged a QSO; includes full QSO details.
     *
     * @return array<string, mixed>
     */
    private function parseQsoLogged(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));
        $offset += 4;
        $dateTimeOff = unpack('Q', substr($packet, $offset, 8))[1] ?? 0;
        $offset += 8;
        $dxCall = $this->readUtf8String($packet, $offset);
        $dxGrid = $this->readUtf8String($packet, $offset);
        $txFrequency = unpack('Q', substr($packet, $offset, 8))[1] ?? 0;
        $offset += 8;
        $mode = $this->readUtf8String($packet, $offset);
        $reportSent = $this->readUtf8String($packet, $offset);
        $reportReceived = $this->readUtf8String($packet, $offset);
        $txPower = $this->readUtf8String($packet, $offset);
        $comments = $this->readUtf8String($packet, $offset);
        $name = $this->readUtf8String($packet, $offset);
        $dateTimeOn = unpack('Q', substr($packet, $offset, 8))[1] ?? 0;
        $offset += 8;
        $operatorCall = $this->readUtf8String($packet, $offset);
        $myCall = $this->readUtf8String($packet, $offset);
        $myGrid = $this->readUtf8String($packet, $offset);
        $exchangeSent = $this->readUtf8String($packet, $offset);
        $exchangeReceived = $this->readUtf8String($packet, $offset);

        return [
            'type' => 'qso_logged',
            'id' => $result['id'] ?? 0,
            'dateTimeOff' => $dateTimeOff,
            'dxCall' => $dxCall,
            'dxGrid' => $dxGrid,
            'txFrequency' => $txFrequency,
            'mode' => $mode,
            'reportSent' => $reportSent,
            'reportReceived' => $reportReceived,
            'txPower' => $txPower,
            'comments' => $comments,
            'name' => $name,
            'dateTimeOn' => $dateTimeOn,
            'operatorCall' => $operatorCall,
            'myCall' => $myCall,
            'myGrid' => $myGrid,
            'exchangeSent' => $exchangeSent,
            'exchangeReceived' => $exchangeReceived,
        ];
    }

    /**
     * Parse Close message (type 6).
     * Close/teardown notification from WSJT-X.
     *
     * @return array<string, mixed>
     */
    private function parseClose(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));

        return [
            'type' => 'close',
            'id' => $result['id'] ?? 0,
        ];
    }

    /**
     * Parse Logged ADIF message (type 10).
     * ADIF record string emitted by WSJT-X.
     *
     * @return array<string, mixed>
     */
    private function parseLoggedAdif(string $packet, int $offset, int $schema): array
    {
        $result = unpack('Nid', substr($packet, $offset, 4));
        $offset += 4;
        $adif = $this->readUtf8String($packet, $offset);

        return [
            'type' => 'logged_adif',
            'id' => $result['id'] ?? 0,
            'adif' => $adif,
        ];
    }

    /**
     * Read a UTF-8 string from packet (QDataStream format: 32-bit length + UTF-8 bytes).
     *
     * @return string
     */
    private function readUtf8String(string $packet, int &$offset): string
    {
        if ($offset + 4 > strlen($packet)) {
            return '';
        }

        $length = unpack('N', substr($packet, $offset, 4))[1] ?? 0;
        $offset += 4;

        if ($length === 0 || $offset + $length > strlen($packet)) {
            return '';
        }

        $string = substr($packet, $offset, $length);
        $offset += $length;
        return $string;
    }
}


