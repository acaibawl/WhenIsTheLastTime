<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class NotificationSettings
{
    public function __construct(
        public ReminderSettings $reminder = new ReminderSettings(),
    ) {}

    /**
     * Create a new instance with updated reminder settings.
     */
    public function withReminder(ReminderSettings $reminder): self
    {
        return new self(reminder: $reminder);
    }
}
