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

- `Application/CategoryRepository` – kontrakt pro čtení úplného a aktivního stromu kategorií včetně lokalizovaných názvů;
- `Application/ProductRepository` – kontrakt pro čtení produktu a překladů;
- `Application/Dto/CategoryTreeDto` a `CategoryTranslationDto` – neměnný lokalizovaný strom kategorií;
- `Application/Dto/ProductDto`, `ProductTranslationDto`, `ProductListItemDto` a `ProductListingDto` – neměnné read-model DTO;
- `Infrastructure/Persistence/DatabaseCategoryRepository` – dávkové načtení kategorií a překladů přes Nette Database Explorer, deterministické řazení a bezpečné sestavení stromu;
- `Infrastructure/Persistence/DatabaseProductRepository` – implementaci přes Nette Database Explorer včetně stránkovaného výpisu aktivních produktů přímo zařazených do kategorie; podporuje řazení podle pozice v kategorii, kódu a data vytvoření;
- `Application/ProductDetailService` a `ProductDetailRepository` – lokalizovaný detail aktivního produktu; read model obsahuje překlad produktu, aktivní varianty s lokalizovanými hodnotami os, obrázky, produktové atributy, zákaznické volby včetně hodnot a směrové vztahy k dalším aktivním produktům; databázový adaptér načítá související data dávkově a nepoužívá N+1 dotazy;
- `Application/ProductRelationRepository` a `ProductRelationService` – lokalizované čtení i transakční správa směrových vztahů; přidání vyžaduje dva aktivní různé produkty, odebrání cílí na přesný typ vztahu a přeřazení přijímá úplný seznam bez duplicit; databázová unikátnost zůstává poslední ochranou proti duplicitám; typy (`accessory`, `alternative`, `compatible`) jsou reprezentovány enumem a opačný směr se nikdy nevytváří automaticky;
- `Domain/Variant/VariantCombinationHash` – deterministickou kanonizaci a SHA-256 hash kombinace variant.

Repozitář mapuje databázové řádky na DTO uvnitř persistence vrstvy. Časové údaje převádí do UTC.

Detail produktu načítá pouze aktivní produkty a aktivní varianty. Překladové názvy atributů, enum hodnot a zákaznických voleb se vybírají výhradně podle explicitního Locale; chybějící překlad zůstává null a nepoužívá skrytý fallback. Ceny a sklad nejsou součástí katalogového detailu a zůstávají ve vlastnictví modulů Pricing a Inventory.

Výpis produktů používá ProductListingDto a ProductListItemDto; položky
obsahují základní produkt a volitelný překlad požadovaného jazyka. Stránkování
je číslováno od 1 a limit je 1 až 100 položek. Výchozí řazení respektuje
product_category.sort_order; další povolené volby jsou kód vzestupně/sestupně
a datum vytvoření nejnovější/nejstarší. Řazení je typované přes ProductSortOrder,
takže uživatelský vstup nikdy netvoří SQL identifikátor. Neaktivní kategorie
ani neaktivní produkty se do výpisu nedostanou. Výpis zahrnuje pouze přímé
přiřazení produktu do kategorie, nikoli automaticky její potomky.

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

## Správa souvisejících produktů

Změny vztahů probíhají přes aplikační `ProductRelationService`, která každou operaci
obaluje sdílenou transakcí. Zdrojový i cílový produkt při přidání musí být aktivní;
sebevazba není povolena. Odebrání odstraňuje pouze přesnou kombinaci zdrojového
produktu, cílového produktu a typu vztahu.

Přeřazení používá úplný seznam `ProductRelationOrderDto`: musí obsahovat všechny
aktuální vztahy zdrojového produktu právě jednou. Částečné pořadí ani duplicitní
položky se nepřijímají. Nové pořadí se ukládá jako souvislá řada od nuly. Směrovost
je zachována a opačný vztah se nikdy nevytváří automaticky.

## Generování slugů

Aplikační `SlugService` vytváří ASCII slug z názvu pomocí ICU transliterace,
sjednocuje oddělovače na pomlčky a kontroluje dostupnost v rámci jazyka.
Při kolizi zkouší suffixy `-2`, `-3` atd. Kontrola rozlišuje překlady produktů
a kategorií; při úpravě lze vyloučit vlastní entitu, aby si zachovala svůj slug.

Dostupnost je předběžná kontrola. Při souběžném zápisu zůstávají konečnou
ochranou unikátní indexy `(locale, slug)` v tabulkách `product_translation`
a `category_translation`.

## Doménové události produktu

Veřejné katalogové události v `Api/Event` (`ProductCreated`, `ProductUpdated`,
`ProductDeleted`) jsou neměnné typované `DomainEvent` kontrakty. Každá nese
kladné `productId`; nepředává zbytečný stav produktu ani osobní údaje.
V této fázi jde o event kontrakty, nikoli o automatický dispatch: emitování bude
zapojeno v příslušných use-case při zavedení produktových zápisů.
