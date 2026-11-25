<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

interface MediaAssetPublicationWriter
{
    /** Publishes an asset and returns its stable opaque public identifier. */
    public function publish(int $id): string;

    /** Immediately revokes public access while retaining the identifier for a later republish. */
    public function unpublish(int $id): void;
}
