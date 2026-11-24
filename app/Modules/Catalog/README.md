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

## Administrační výpis produktů (F5-15)

Catalog poskytuje veřejný čtecí kontrakt `Api/ProductAdminListReader` pro administrační seznam produktů. Vstupem je neměnný `ProductAdminListFilter` a explicitní `Locale`; výstupem je stránkovaný `ProductAdminListing` s typovanými položkami `ProductAdminListItem`.

- Filtry lze kombinovat: přímé přiřazení ke kategorii, stav produktu (`draft`, `active`, `archived`) a typ (`physical`, `digital`, `service`).
- Výpis zahrnuje všechny životní stavy; nejde o zákaznický katalog aktivních produktů.
- Výchozí stránkování je 12 položek, povolený limit je 1 až 100. Pořadí je stabilní podle ID vzestupně.
- Názvy produktů a kategorií se načítají pro explicitní locale hromadně až pro aktuální stránku. Chybějící překlad názvu produktu zůstává `null`; UI zobrazí neutrální náhradní znak. Kategorie jsou uvedeny podle přímých vazeb produktu, nikoliv automaticky podle potomků.
- `Infrastructure/Persistence/DatabaseProductAdminListRepository` provádí filtrování, počítání a stránkování přes Nette Database Explorer; překlady a názvy kategorií načítá dávkově bez N+1 dotazů. `ActiveRow` neopouští persistence vrstvu.
- Admin UI používá `UI/Admin/Products/ProductsPresenter`, adaptér `ProductAdminDataGridDataSource` a továrnu `ProductAdminDataGridFactory`. Ublaboo DataGrid zůstává v prezentační vrstvě; Catalog nezná knihovnu.
- Serverová oprávnění kontroluje presenter přes veřejný kontrakt `CatalogPermission`; samotný read model záměrně neobsahuje uživatelskou identitu ani autorizační rozhodnutí.

Ověření pokrývají `ProductAdminListFilter.phpt`, `DatabaseProductAdminListRepositoryIntegration.phpt` a `ProductAdminDataGridDataSource.phpt`. Integrační překlady DataGridu v češtině a angličtině ověřuje `AdminDataGridTranslatorIntegration.phpt`.

## Administrační editor produktu – základní údaje (F5-16)

Zápis základních údajů produktu zpřístupňuje veřejný kontrakt
`Api/ProductWriter` s veřejnými enumy `ProductAdminStatus` a `ProductAdminType`.
Jeho interní implementací je aplikační `ProductWriteService`, který převádí
veřejné hodnoty na doménové enumy a používá `ProductWriteRepository`.
Prezentační vrstva nezná aplikační službu ani doménové typy a nikdy nezapisuje
přímo do databáze. Databázový adaptér používá Nette Database Explorer a
nepředává `ActiveRow` mimo persistence vrstvu.

- Vytvoření a úprava produktu probíhá v jedné sdílené transakci.
- Serverová kontrola `CatalogPermission::canManage()` je součástí use-case;
  skrytí tlačítka ani ochrana presenteru ji nenahrazují.
- Kód produktu se normalizuje a validuje; unikátnost chrání také databázový index.
- Pro každý aktivní jazyk musí být dodán neprázdný název. Krátký popis,
  dlouhý popis a SEO metadata jsou volitelná a ukládají se samostatně pro jazyk.
- Slug se generuje přes existující `SlugService`; kolize řeší suffix a
  konečnou ochranu poskytuje unikátní index `(locale, slug)`.
- Přiřazení kategorií se v rámci stejné transakce kompletně nahradí a jejich
  pořadí odpovídá pořadí odeslanému z editoru. Duplicitní a neexistující ID
  jsou odmítnuta aplikační vrstvou.
- Editor načítá produkt, překlady a přiřazení přes veřejný kontrakt
  `ProductEditorReader`. Zápis provádí přes veřejný `ProductWriter`. Seznam
  daňových tříd je pouze čtecí nabídka;
  platnost ID při zápisu znovu ověřuje persistence vrstva.
- Admin UI nabízí vytvoření a úpravu na samostatných stránkách; seznam
  produktů zůstává odděleným DataGrid read modelem.
- Handler formuláře deklaruje návratový typ `array`, aby Nette Forms převedlo
  vnořené jazykové kontejnery z `ArrayHash` na pole; validace tak předává
  úplná data do veřejného zapisovacího kontraktu. Úspěšný zápis používá
  Post/Redirect/Get a Nette flash session.

Ověření zapisovací vrstvy pokrývá
`tests/Modules/Catalog/ProductWriteServiceIntegration.phpt`: odmítnutí
anonymního zápisu, vytvoření produktu a překladů pro aktivní jazyky,
přiřazení kategorií, úprava stavu/typu, odstranění přiřazení a validace kódu.

F5-16 bylo dokončeno a ověřeno uživatelem v prohlížeči: změny základních údajů
se po opětovném otevření načtou z databáze a úspěšný zápis zobrazí flash zprávu.
Handler formuláře přijímá číselné ID selectů v obou podobách (`string` i `int`)
a převádí je na validované celé číslo před voláním veřejného `ProductWriter`.

## Administrační matice variant (F5-17)

Veřejný read kontrakt `Api/ProductVariantMatrixReader` vrací osy produktu,
dostupné hodnoty, existující varianty a chybějící kombinace jako neměnná DTO.
Databázovou implementací je `Infrastructure/Persistence/DatabaseProductVariantMatrixRepository`.
Read model zahrnuje i neaktivní varianty, aby matice nikdy neztratila jejich
identitu ani obchodní vazby.

Dostupné hodnoty se v této iteraci získávají podle typu osy:
- `enum`: všechny hodnoty příslušného atributu z `attribute_value` v pořadí
  definovaném číselníkem; popisky se načítají pro explicitní locale.
- `bool`: pevné hodnoty false a true.
- `number` a `text`: unikátní hodnoty, které jsou již uloženy
  u existujících variant produktu. Pokud zatím žádná taková hodnota neexistuje,
  matice generování zablokuje a zobrazí vysvětlení. Vstup nových hodnot pro
  tyto typy je navazující práce správy atributů/os; nesmí se odvozovat
  z klientského payloadu bez serverové validace.

Kartézský součin dostupných hodnot je omezen na 256 kombinací. Matice zobrazí
všechny kombinace a umožní vybrat pouze chybějící; existující kombinace jsou
pouze ke čtení. Veřejný zápisový kontrakt `Api/ProductVariantGenerator`
implementuje `Application/ProductVariantGenerationService` a jeho
`DatabaseProductVariantGenerationRepository`:
- před zápisem znovu ověří produkt, úplnou sadu os, datové typy a vlastnictví
  enum hodnot;
- vypočítá identitu výhradně přes `VariantCombinationHash`;
- v jedné transakci vytvoří pouze dosud neexistující kombinace a jejich
  typované hodnoty; opakované odeslání stejné kombinace je idempotentní;
- nové varianty dostanou aktivní stav, nejsou výchozí a dostanou dočasné
  deterministické SKU `V{productId}-{prvních 40 znaků SHA-256}`;
  ruční správa SKU, EAN, hmotnosti a výchozí varianty patří do F5-18;
- existující varianty ani jejich SKU, aktivitu, výchozí příznak, ceny, sklad,
  obrázky a historické vazby se nemění.

Aplikační zápis je chráněn `CatalogPermission::canManage()` a používá
sdílený `Transaction`. Ceny a sklad zůstávají vlastnictvím modulů
Pricing a Inventory. Implementace nevyžaduje změnu databázového schématu;
nové cs/en texty matice dodávají idempotentní seedy
`migrations/dummy-data/2026-11-24-060000-seed-catalog-product-variants-admin.sql`
a `migrations/dummy-data/2026-11-24-070000-seed-catalog-variant-matrix-limit.sql`.
Překročení limitu vrací veřejnou výjimku `Api/VariantMatrixTooLargeException`,
kterou Admin UI převádí na lokalizované upozornění.

Ověření pokrývají `ProductVariantMatrixIntegration.phpt` a
`ProductVariantGenerationIntegration.phpt`; uživatel potvrdil úspěšné lokální kontroly
i funkčnost matice v prohlížeči. Kanonizaci
a shodu PHP/MySQL SHA-256 dále ověřují existující testy
`VariantCombinationHash.phpt`, `VariantCombinationHashIntegration.phpt`
a `VariantSelectorIntegration.phpt`.
## Správa konkrétní varianty (F5-18)

Veřejný čtecí kontrakt `Api/ProductVariantEditorReader` vrací
`ProductVariantEditorData` pro konkrétní variantu a explicitní locale.
Zápis probíhá přes `Api/ProductVariantWriter`, který implementuje
`Application/ProductVariantWriteService` a interní
`ProductVariantWriteRepository`. Databázovým adaptérem je
`DatabaseProductVariantEditorRepository`; ActiveRow zůstává uvnitř
persistence vrstvy.

- SKU se validuje jako 1–64 povolených ASCII znaků a jeho globální unikátnost
  chrání aplikační kontrola i databázový unikátní index.
- EAN je volitelný; podporován je EAN-8 a EAN-13 včetně ověření kontrolní
  číslice. Globální unikátnost a formát délky/číselných znaků chrání databáze.
- Hmotnost je volitelná v celých gramech a odpovídá `INT UNSIGNED`.
- Výchozí variantou může být pouze aktivní varianta. Změna nejprve zruší
  dosavadní výchozí příznak produktu a následně nastaví vybranou variantu
  uvnitř jedné transakce; unikátní databázový index zůstává konečnou ochranou.
- Úprava nezasahuje do kombinace atributů, cen, skladu ani dalších vazeb.
- Dopředná migrace `2026-11-24-080000-product-variant-ean.sql` přidává
  nullable EAN a globální unikátní index. Texty cs/en dodává seed
  `2026-11-24-080000-seed-catalog-product-variant-editor.sql`.

Integrační ověření pokrývá `ProductVariantWriteIntegration.phpt`;
kontrolní číslice EAN-8/EAN-13 ověřuje `VariantEan.phpt`. GitHub Actions run
`45344682171` prošel: 81 testů, PHPStan, coding standard, architektura a dopředné migrace MySQL 8.4; test pokrývá také samostatný DB unikátní index EAN.

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
