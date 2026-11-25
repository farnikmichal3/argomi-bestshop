<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Api\MediaAccessDeniedException;
use App\Modules\Media\Api\MediaAssetPublicationWriter;
use App\Modules\Media\Api\MediaPermission;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

final readonly class MediaAssetPublicationService implements MediaAssetPublicationWriter
{
    public function __construct(
        private MediaAssetRepository $repository,
        private MediaPermission $permission,
        private Transaction $transaction,
    ) {}

    public function publish(int $id): string
    {
        $this->assertCanManage();
        if ($id < 1) {
            throw new InvalidArgumentException('Médium nebylo nalezeno.');
        }

        $asset = $this->repository->findById($id);
        if ($asset === null) {
            throw new InvalidArgumentException('Médium nebylo nalezeno.');
        }

        $token = $asset->publicToken ?? bin2hex(random_bytes(32));
        $this->transaction->run(fn() => $this->repository->publish($id, $token));

        return $token;
    }

    public function unpublish(int $id): void
    {
        $this->assertCanManage();
        if ($id < 1) {
            throw new InvalidArgumentException('ID média musí být kladné.');
        }
        $this->transaction->run(fn() => $this->repository->unpublish($id));
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new MediaAccessDeniedException('Publikování médií bylo odepřeno.');
        }
    }
}
