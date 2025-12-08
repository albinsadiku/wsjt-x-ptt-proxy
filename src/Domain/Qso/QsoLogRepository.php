<?php

declare(strict_types=1);

namespace App\Domain\Qso;

interface QsoLogRepository
{
    /**
     * @return void
     */
    public function store(QsoLogEntry $entry): void;

    /**
     * @return QsoLogEntry[]
     */
    public function all(): array;
}

