<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Settings;

readonly class MiscSettings
{
    public function __construct(
        public bool $showTutorial = true,
    ) {}

    /**
     * Create a new instance with updated showTutorial.
     */
    public function withShowTutorial(bool $showTutorial): self
    {
        return new self(showTutorial: $showTutorial);
    }
}
