<?php

namespace App\Services;

class ImportResult
{
    public function __construct(
        public readonly array $errors = [],
        public readonly int $errorCount = 0,
        public readonly int $updated = 0,
        public readonly int $created = 0,
        public readonly bool $dryRun = false,
    ) {}

    public function failed(): bool
    {
        return $this->errorCount > 0;
    }
}