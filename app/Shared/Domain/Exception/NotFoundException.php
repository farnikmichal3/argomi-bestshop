<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Označuje, že požadovaný doménový objekt nebo záznam nebyl nalezen.
 *
 * Výjimka popisuje doménový stav a sama neurčuje HTTP odpověď.
 * Převod na HTTP 404 nebo jiný výsledek patří do aplikační či prezentační vrstvy.
 */
class NotFoundException extends DomainException {}
