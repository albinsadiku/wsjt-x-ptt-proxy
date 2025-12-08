<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Config\AppConfig;
use App\Domain\Qso\QsoLogEntry;
use App\Domain\Qso\QsoLogRepository;
use RuntimeException;

final class FileQsoLogRepository implements QsoLogRepository
{
    /**
     * @var string
     */
    private string $logFile;

    /**
     * @return void
     */
    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? AppConfig::storagePath('logs/qso-log.json');
        $dir = dirname($this->logFile);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create log directory: {$dir}");
        }
        if (!file_exists($this->logFile)) {
            file_put_contents($this->logFile, '[]');
        }
    }

    /**
     * Append a QSO entry to the JSON log.
     *
     * @return void
     */
    public function store(QsoLogEntry $entry): void
    {
        $entries = $this->decode();
        $entries[] = $entry->toArray();
        file_put_contents($this->logFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * @return QsoLogEntry[]
     */
    public function all(): array
    {
        $entries = $this->decode();
        return array_map(static fn (array $row): QsoLogEntry => new QsoLogEntry(
            $row['callSign'],
            $row['grid'],
            $row['mode'],
            $row['rstSent'],
            $row['rstRecv'],
            (int) $row['timestamp'],
            $row['notes'] ?? null
        ), $entries);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decode(): array
    {
        $data = file_get_contents($this->logFile);
        return $data ? json_decode($data, true, 512, JSON_THROW_ON_ERROR) : [];
    }
}

