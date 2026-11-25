<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

interface MediaFileStorage
{
    public function write(string $key, string $contents): void;

    public function read(string $key): string;

    public function delete(string $key): void;

    public function exists(string $key): bool;
}
