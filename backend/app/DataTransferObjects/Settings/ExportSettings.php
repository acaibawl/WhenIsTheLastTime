<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class ExportSettings
{
    public function __construct(
        public ?string $lastExportedAt = null,
    ) {}

    /**
     * Create a new instance with updated lastExportedAt.
     */
    public function withLastExportedAt(?string $lastExportedAt): self
    {
        return new self(lastExportedAt: $lastExportedAt);
    }
}
