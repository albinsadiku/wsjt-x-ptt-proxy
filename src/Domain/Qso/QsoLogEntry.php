<?php

declare(strict_types=1);

namespace App\Domain\Qso;

final class QsoLogEntry
{
    /**
     * @var string
     */
    public string $callSign;
    /**
     * @var string
     */
    public string $grid;
    /**
     * @var string
     */
    public string $mode;
    /**
     * @var string
     */
    public string $rstSent;
    /**
     * @var string
     */
    public string $rstRecv;
    /**
     * @var int
     */
    public int $timestamp;
    /**
     * @var ?string
     */
    public ?string $notes;

    /**
     * @return void
     */
    public function __construct(
        string $callSign,
        string $grid,
        string $mode,
        string $rstSent,
        string $rstRecv,
        int $timestamp,
        ?string $notes = null
    ) {
        $this->callSign = $callSign;
        $this->grid = $grid;
        $this->mode = $mode;
        $this->rstSent = $rstSent;
        $this->rstRecv = $rstRecv;
        $this->timestamp = $timestamp;
        $this->notes = $notes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'callSign' => $this->callSign,
            'grid' => $this->grid,
            'mode' => $this->mode,
            'rstSent' => $this->rstSent,
            'rstRecv' => $this->rstRecv,
            'timestamp' => $this->timestamp,
            'notes' => $this->notes,
        ];
    }
}

