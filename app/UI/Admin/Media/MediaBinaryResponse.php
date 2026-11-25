<?php

declare(strict_types=1);

namespace App\UI\Admin\Media;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

final readonly class MediaBinaryResponse implements Response
{
    public function __construct(
        private string $contents,
        private string $mimeType,
    ) {}

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType($this->mimeType);
        $httpResponse->setHeader('X-Content-Type-Options', 'nosniff');
        $httpResponse->setHeader('Cache-Control', 'private, max-age=300');
        echo $this->contents;
    }
}
