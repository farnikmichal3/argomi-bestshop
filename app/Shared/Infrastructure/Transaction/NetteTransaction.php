<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Transaction\Transaction;
use Nette\Database\Explorer;

/**
 * Produkční implementace transakčního rozhraní pomocí Nette Database.
 *
 * Závislost na Nette zůstává v infrastruktuře. Aplikační callback
 * nedostává Explorer ani databázové spojení, takže nemůže obejít
 * aplikační hranici a navázat se na konkrétní databázovou knihovnu.
 */
final class NetteTransaction implements Transaction
{
    /**
     * Připraví adaptér nad sdíleným Nette Database Explorerem.
     */
    public function __construct(
        private readonly Explorer $database,
    ) {}

    /**
     * Předá operaci správě transakcí Nette Database.
     *
     * Nette při úspěšném dokončení nejvyššího callbacku transakci
     * potvrdí a při výjimce nebo chybě, která opustí nejvyšší callback,
     * ji vrátí zpět. Vnořená volání pouze sdílejí stejnou transakci:
     * Nette pro ně nevytváří savepointy. Pokud vnější callback zachytí
     * výjimku z vnořeného volání, vnitřní změny zůstávají součástí
     * vnější transakce. Původní chybu necháváme propagovat beze změny.
     *
     * @template TResult
     *
     * @param callable(): TResult $operation Operace, která se má provést.
     *
     * @return TResult Výsledek předaného callbacku.
     */
    public function run(callable $operation): mixed
    {
        return $this->database->transaction(
            static fn(): mixed => $operation(),
        );
    }
}
