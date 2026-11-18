# Modul Catalog

Modul `Catalog` vlastní zákaznický katalog: kategorie, produkty, varianty,
atributy, volby zákazníka, obrázky, související produkty a jejich čtecí modely.

## Hranice modulu

- Veřejné kontrakty pro ostatní moduly patří do `Api/`.
- Doménové objekty a invarianty patří do `Domain/`.
- Use-cases, aplikační služby a interní DTO patří do `Application/`.
- Databázové adaptéry a DI konfigurace patří do `Infrastructure/`.
- `ActiveRow` nesmí opustit persistence vrstvu; ven se předávají typovaná DTO.
- Ostatní moduly smějí používat pouze veřejné `Api/`, nikoliv interní implementaci.
- Prezentační vrstva komunikuje s katalogem přes veřejné API nebo aplikační kontrakty
  určené pro UI; dotazy do databáze nepatří do presenterů.

## Existující základ

První čtecí řez katalogu již obsahuje:

- `Application/ProductRepository` – kontrakt pro čtení produktu a překladů;
- `Application/Dto/ProductDto` a `ProductTranslationDto` – neměnné read-model DTO;
- `Infrastructure/Persistence/DatabaseProductRepository` – implementaci přes Nette Database Explorer;
- `Domain/Variant/VariantCombinationHash` – deterministickou kanonizaci a SHA-256 hash kombinace variant.

Repozitář mapuje databázové řádky na DTO uvnitř persistence vrstvy. Časové údaje převádí do UTC.

## DI registrace

`Infrastructure/DI/CatalogExtension` je automaticky objevena konvencí
`ModuleRegistry` v Bootstrapu. Služby odpovídající globálním konvencím
`*Repository`, `*Factory` a `*Service` se registrují přes
`config/services.neon`; extension je nesmí duplicitně přidávat.

Modulová DI extension je vyhrazena pro skutečnou modulovou konfiguraci a
explicitní vazby, které nelze bezpečně pokrýt automatickým vyhledáváním.

## Databáze a překlady

Catalog vlastní své katalogové tabulky a systémové texty v
`catalog_message_translation`. Výchozí systémové texty se dodávají
idempotentními SQL seedy; runtime překlady se načítají výhradně z databáze.
Historické migrace se neupravují, změny se dodávají dopřednými migracemi.

## Další rozvoj

Podrobný postup implementace je veden v `docs/ROADMAP.md` pod F5.
