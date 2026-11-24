<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use HTMLPurifier;
use HTMLPurifier_Config;

final class ProductDescriptionSanitizer
{
    private readonly HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set(
            'HTML.Allowed',
            'p,br,strong,b,em,i,u,s,ul,ol,li,h2,h3,h4,blockquote,'
            . 'a[href|title|target|rel],table,thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],'
            . 'span[title],div',
        );
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('CSS.AllowedProperties', []);
        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return $this->purifier->purify($html);
    }
}
