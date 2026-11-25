<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

interface MediaAssetWriter
{
    /**
     * Stores an immutable original and its generated derivatives.
     *
     * @throws MediaAccessDeniedException
     * @throws \InvalidArgumentException
     */
    public function upload(string $source, string $originalFilename): int;

    /**
     * Deletion is rejected while any module references the asset.
     *
     * @throws MediaAccessDeniedException
     */
    public function delete(int $id): void;
}
