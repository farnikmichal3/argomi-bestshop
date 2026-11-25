<?php

declare(strict_types=1);

namespace App\UI\Front\Media;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

final readonly class PublicMediaResponse implements Response
{
    public function __construct(
        private string $contents,
        private string $mimeType,
    ) {}

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType($this->mimeType);
        $httpResponse->setHeader('X-Content-Type-Options', 'nosniff');
        // Do not let browsers/CDNs retain a public copy after an administrator unpublishes it.
        $httpResponse->setHeader('Cache-Control', 'no-store, max-age=0');
        echo $this->contents;
    }
}
