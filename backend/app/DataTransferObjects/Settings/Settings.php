<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class Settings
{
    public function __construct(
        public ExportSettings $export = new ExportSettings(),
        public NotificationSettings $notification = new NotificationSettings(),
        public MiscSettings $misc = new MiscSettings(),
    ) {}

    /**
     * Create a new instance with updated export settings.
     */
    public function withExport(ExportSettings $export): self
    {
        return new self(
            export: $export,
            notification: $this->notification,
            misc: $this->misc,
        );
    }

    /**
     * Create a new instance with updated notification settings.
     */
    public function withNotification(NotificationSettings $notification): self
    {
        return new self(
            export: $this->export,
            notification: $notification,
            misc: $this->misc,
        );
    }

    /**
     * Create a new instance with updated misc settings.
     */
    public function withMisc(MiscSettings $misc): self
    {
        return new self(
            export: $this->export,
            notification: $this->notification,
            misc: $misc,
        );
    }
}
