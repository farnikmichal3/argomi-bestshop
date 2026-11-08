# Pokyny pro AI agenty – ArgoMi BestShop

Tento soubor je provozní instrukce pro práci v repozitáři. Produktová a architektonická rozhodnutí jsou v `docs/`; při rozporu se řiď aktuálním rozhodnutím autora a kanonickými dokumenty.

## Nejdříve si přečti

1. `docs/README.md`
2. `docs/ZAMER.md`
3. `docs/STRUKTURA.md`
4. `docs/DATABASE.md` nebo `docs/TRANSLATIONS.md` podle oblasti
5. `docs/ROADMAP.md`

Před změnou vždy prohlédni aktuální kód, konfiguraci, migrace a návazné testy. Nepředpokládej, že plánovaný úkol už je implementovaný.

## Projekt v kostce

- PHP 8.2+, Nette 3.2, Latte 3.
- MySQL 8.x, InnoDB, utf8mb4.
- Nette Database Explorer jako aplikační přístup k databázi.
- Nextras Migrations pro čisté SQL migrace.
- Sass + esbuild, Bootstrap 5.3.8 + Bootswatch Cosmo, Naja 3.
- PHPStan level max, Nette Tester.
- Vite se nepoužívá.
- Repozitář je veřejný, ale projekt není open source; všechna práva vyhrazena.

## Struktura

Hlavní strom je `app/Core`, `app/Modules`, `app/Presentation`, `app/Shared` a `app/UI`. UI se dělí na `Admin`, `Front` a `Shared`. Přesné odpovědnosti a strom jsou v `docs/STRUKTURA.md`.

## Závazná pravidla kódu

- V každém PHP souboru používej `declare(strict_types=1);`.
- Preferuj konstruktorovou injekci; žádný service locator ani statické získávání služeb.
- Doménová pravidla patří do Domain/Application, ne do presenterů.
- Databázové dotazy patří do repozitářů modulu. Presentery a UI nesmějí dotazovat databázi přímo.
- `ActiveRow` neopouští persistence vrstvu; ven jdou typované DTO/value objecty.
- Moduly používají pouze veřejné API jiných modulů.
- Nezaváděj závislosti mezi moduly přes jejich interní třídy.
- Nepoužívej `noescape` v Latte.
- Nikdy neskládej SQL z nedůvěryhodných řetězců; používej parametrizované dotazy a API Exploreru.
- Peníze reprezentuj celočíselnými minor units a měnou, ne floatem. Množství ukládej desetinným typem.
- Systémové překlady dodávané aplikací jsou chráněné. Administrátor smí spravovat vlastní překlady, nikoliv přepisovat systémové zdroje.
- Neměň historické SQL migrace; změny databáze přidávej novou dopřednou migrací.
- Demo data zůstávají v produkci. Nikdy však nesmějí spustit skutečnou platbu, expedici ani zákaznický e-mail.

## Bezpečnost

- Nikdy nevypisuj ani necommituj tajné údaje, tokeny, hesla nebo obsah lokální konfigurace.
- `app/`, `config/`, `log/` a `temp/` nesmí být veřejně přístupné.
- Tracy musí být v produkci vypnutá.
- Vstupy validuj, výstupy kontextově escapuj.
- HTML obsah sanitizuj schváleným způsobem.
- Ověřuj autorizaci na serveru; skrytí tlačítka nestačí.
- Změny autentizace, oprávnění, plateb, webhooků a osobních údajů vyžadují bezpečnostní testy.
- Produkční migrace vyžaduje ověřenou zálohu.

## Ověření změn

Před označením úkolu jako HOTOVO:
1. spusť relevantní testy,
2. spusť PHPStan level max,
3. ověř bezpečnost a oprávnění,
4. aktualizuj dokumentaci modulu/ADR,
5. aktualizuj ROADMAP a její souhrny.

Nespouštěj destruktivní příkazy typu reset databáze bez výslovného pokynu autora a potvrzení, že jde o vývojovou/testovací databázi.

## Práce s ROADMAP

- Zachovávej ID úkolů.
- Používej pouze definované stavy.
- Nejvýše tři úkoly mohou být ROZDĚLÁNO současně.
- Každou změnu architektonického rozhodnutí zapiš do ZÁMĚRU nebo ADR.
- HOTOVO znamená ověřený výsledek, ne pouze vytvořený soubor nebo prázdnou složku.
