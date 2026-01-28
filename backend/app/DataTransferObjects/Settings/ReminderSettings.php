<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class ReminderSettings
{
    public function __construct(
        public bool $enabled = false,
        public TimingSettings $timing = new TimingSettings(),
        public string $targetEvents = 'week',
    ) {}

    /**
     * Create a new instance with updated enabled.
     */
    public function withEnabled(bool $enabled): self
    {
        return new self(
            enabled: $enabled,
            timing: $this->timing,
            targetEvents: $this->targetEvents,
        );
    }

    /**
     * Create a new instance with updated timing.
     */
    public function withTiming(TimingSettings $timing): self
    {
        return new self(
            enabled: $this->enabled,
            timing: $timing,
            targetEvents: $this->targetEvents,
        );
    }

    /**
     * Create a new instance with updated targetEvents.
     */
    public function withTargetEvents(string $targetEvents): self
    {
        return new self(
            enabled: $this->enabled,
            timing: $this->timing,
            targetEvents: $targetEvents,
        );
    }
}
