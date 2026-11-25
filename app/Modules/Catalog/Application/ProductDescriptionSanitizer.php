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
            . 'span[title],div,img[src|alt|width|height]',
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

        $html = $this->preparePublicMediaImages($html);

        return str_replace(
            'https://media.invalid/media/',
            '/media/',
            $this->purifier->purify($html),
        );
    }

    private function preparePublicMediaImages(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<!doctype html><html><body><div id="product-description-root">' . $html . '</div></body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
            );
            $root = $document->getElementById('product-description-root');
            if (!$root instanceof \DOMElement) {
                return '';
            }

            foreach (iterator_to_array($root->getElementsByTagName('img')) as $image) {
                $source = $image->getAttribute('src');
                if (preg_match('~^/media/[a-f0-9]{64}/detail$~D', $source) !== 1) {
                    $image->parentNode?->removeChild($image);
                    continue;
                }

                $image->setAttribute('src', 'https://media.invalid' . $source);
            }

            $prepared = '';
            foreach ($root->childNodes as $child) {
                $prepared .= $document->saveHTML($child);
            }

            return $prepared;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }
}
