<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class TimingSettings
{
    public function __construct(
        public string $type = 'daily',
        public string $time = '09:00',
        public ?int $dayOfWeek = null,
        public ?int $dayOfMonth = null,
    ) {}

    /**
     * Create a new instance with updated type.
     */
    public function withType(string $type): self
    {
        return new self(
            type: $type,
            time: $this->time,
            dayOfWeek: $this->dayOfWeek,
            dayOfMonth: $this->dayOfMonth,
        );
    }

    /**
     * Create a new instance with updated time.
     */
    public function withTime(string $time): self
    {
        return new self(
            type: $this->type,
            time: $time,
            dayOfWeek: $this->dayOfWeek,
            dayOfMonth: $this->dayOfMonth,
        );
    }

    /**
     * Create a new instance with updated dayOfWeek.
     */
    public function withDayOfWeek(?int $dayOfWeek): self
    {
        return new self(
            type: $this->type,
            time: $this->time,
            dayOfWeek: $dayOfWeek,
            dayOfMonth: $this->dayOfMonth,
        );
    }

    /**
     * Create a new instance with updated dayOfMonth.
     */
    public function withDayOfMonth(?int $dayOfMonth): self
    {
        return new self(
            type: $this->type,
            time: $this->time,
            dayOfWeek: $this->dayOfWeek,
            dayOfMonth: $dayOfMonth,
        );
    }
}
