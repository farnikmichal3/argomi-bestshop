# Modul Catalog

Modul `Catalog` vlastní zákaznický katalog: kategorie, produkty, varianty,
atributy, volby zákazníka, přiřazení médií, související produkty a jejich čtecí modely.

## Hranice modulu

- Veřejné kontrakty pro ostatní moduly patří do `Api/`.
- Fyzické mediální soubory, originály, deriváty a knihovna médií patří do modulu Media. Catalog vlastní pouze produktové/variantní vazby a jejich pořadí, ALT text a hlavní obrázek; registrace používá `MediaAssetWriter` a `MediaAssetReader` a fyzické úložiště nezná.
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
- `Api/ProductRelationReader` a `ProductRelationWriter` – veřejná hranice pro administrační správu vztahů; veřejné DTO oddělují UI od interní aplikační vrstvy. Reader poskytuje lokalizované existující vztahy a aktivní kandidátní produkty; writer deleguje přidání, odebrání a úplné přeřazení do transakční `ProductRelationService`.
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

## Administrační správa souvisejících produktů (F5-23)

Editor produktu obsahuje samostatnou záložku Související produkty. Veřejné kontrakty `ProductRelationReader` a `ProductRelationWriter` zpřístupňují UI pouze typovaná DTO a enum `ProductRelationKind`; interní aplikační služba ani databázové řádky neopouštějí modul. Lze přidávat, odebírat a kompletně přeřazovat směrové vztahy typu doplněk, alternativa a kompatibilní produkt. Kandidáty tvoří pouze jiné aktivní produkty; duplicitní vazbu odmítá aplikační vrstva i unikátní databázový index. Přesun pořadí posílá úplný seznam vztahů a probíhá transakčně. Opačný vztah se automaticky nevytváří.

## Výběr médií v HugeRTE (F5-22)

Produktový editor nezískává soubory přímo z filesystemu ani z administračního binary endpointu. Picker používá Admin Media endpoint, který vrací pouze explicitně publikované položky. Do popisu se ukládá veřejná URL ve tvaru /media/{token}/detail; serverový ProductDescriptionSanitizer povoluje pouze tuto interní cestu s validním 64znakovým hex tokenem. HTMLPurifier zůstává aktivní a externí, data:, protocol-relative i administrační URL obrázků jsou odmítnuty. Veřejný Media reader navíc kontroluje stav publikování a vydává pouze derivát, nikoliv originál.

## Produktové obrázky a přiřazení médií (F5-22)

Catalog vlastní pouze vazby mezi produktem/variantou a položkou z Media. Veřejné kontrakty `ProductImageReader` a `ProductImageWriter` poskytují seznam, přiřazení, úpravu ALT textu a pořadí, odstranění vazby a nastavení hlavního obrázku. Samotný soubor, originál a deriváty zůstávají ve vlastnictví Media; odstranění produktové vazby soubor nemaže. Přiřazení ověřuje existenci mediální položky přes `MediaAssetReader` a databázový FK ověřuje vlastnictví varianty produktem. Hlavní obrázek je jedinečný pro produktovou nebo konkrétní variantní skupinu.

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


## Administrační správa atributů (F5-19)

Administrační čtení používá veřejný kontrakt `Api/AttributeAdminReader` a zápisy `Api/AttributeWriter`. Interní `AttributeAdminQueryService` a `AttributeWriteService` oddělují read model od transakční správy; adaptér `DatabaseAttributeRepository` implementuje databázové kontrakty přes Nette Database Explorer. `ActiveRow` neopouští persistence vrstvu.

- Atribut má unikátní strojový kód, typ `text`, `number`, `enum` nebo `bool`, volitelnou měrnou jednotku (pouze pro `number`), příznak filtru a pořadí.
- Název atributu i popisky enum hodnot jsou povinné pro každý aktivní jazyk. Aktivní jazyky se získávají dynamicky z `LocaleProvider`; UI ani aplikační služba nemají pevně zakódovaný seznam cs/en.
- Enum hodnoty mají vlastní kód, pořadí a lokalizované popisky. Ostatní typy hodnoty v číselníku nepoužívají.
- Vytvoření, úprava a mazání probíhá přes aplikační službu a sdílenou `Transaction`. Duplicitní kódy chrání aplikační kontrola i unikátní indexy.
- Typ atributu nelze změnit, pokud má již hodnoty nebo je použit jako osa variant. Atribut použitý jako osa nelze odstranit; enum hodnotu použitou existující variantou nelze odstranit. Databázové FK `RESTRICT` zůstávají poslední ochranou obchodních vazeb.
- F5-19 nevyžaduje změnu struktury databáze; systémové texty administrace dodávají idempotentní seedy `2026-11-24-090000-seed-catalog-attribute-admin.sql` a `2026-11-24-100000-seed-admin-attributes-navigation.sql`.

## Editor produktových atributů a os variant (F5-20)

Administrační editor základních údajů je rozšířen o samostatnou kartu
konfigurace produktu. UI čte dostupné atributy, lokalizované názvy, jednotky,
enum možnosti, uložené hodnoty a osy přes veřejný
`Api/ProductEditorConfigurationReader`. Zápis probíhá samostatným formulářem
přes `Api/ProductConfigurationWriter`; prezentační vrstva nezná aplikační
repository ani databázové tabulky.

- Podporované hodnoty jsou `text`, `number`, `enum` a `bool`. Prázdné
  pole znamená, že produkt daný atribut nemá přiřazen; typové a rozsahové
  kontroly vždy znovu provádí `ProductConfigurationService`.
- Enum select nabízí pouze hodnoty příslušného atributu; aplikační služba
  znovu ověřuje jejich příslušnost. Číselné hodnoty se předávají jako řetězce,
  aby se neztratila desetinná přesnost.
- Osy variant se vybírají samostatně a každá má explicitní pořadí. Duplicitní
  nebo nekladné pořadí je odmítnuto. Pokud již produkt obsahuje varianty,
  aplikační služba nedovolí změnit množinu os; povoleno je pouze přeřazení.
- Základní údaje a konfigurace se ukládají oddělenými formuláři a transakcemi.
  Vytvoření produktu přesměruje do editace, kde lze konfiguraci doplnit.
- Systémové popisky UI jsou v databázovém seedu
  `2026-11-24-110000-seed-catalog-product-configuration-editor.sql`.
- Textové atributy jsou prostý text a Latte je escapuje. HTMLPurifier zůstává
  povinný pouze pro HTML popisy produktu; obsah atributů se nikdy nepovažuje
  za důvěryhodné HTML.

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

Administrační editor poskytuje samostatnou záložku pro správu těchto vztahů.
Přidávat lze pouze jiné aktivní produkty; typ vztahu je explicitní
(doplněk, alternativa nebo kompatibilní produkt). Ověření dokončení F5-23
zahrnovalo úspěšné lokální migrace a testy i ruční kontrolu přidávání,
odebírání, řazení a opětovného načtení v administraci.

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


## Bezpečné HTML popisů produktů (F5-20)

Dlouhé lokalizované popisy se před uložením vždy čistí na serveru přes
Application/ProductDescriptionSanitizer (HTMLPurifier). Klientský editor
HugeRTE není bezpečnostní hranice a jeho výstup se nikdy nepovažuje za důvěryhodný.

Povolené jsou pouze základní strukturální a formátovací prvky, seznamy,
odkazy a tabulky. Skripty, obslužné atributy událostí, nebezpečná URL schémata,
vkládané styly a další nepovolený obsah jsou odstraněny. Povolená URL schémata
jsou HTTP, HTTPS a mailto. Sanitizace probíhá uvnitř transakční aplikační služby
před předáním popisu do persistence vrstvy; ostatní SEO a krátké texty zůstávají
prostým textem a jsou escapovány při výstupu.


## Oprávnění správy katalogu (F5-26)

Veřejný kontrakt `Api/CatalogPermission` odděluje čtení katalogu
(`canView()`) od správy (`canManage()`). Aplikační
`CatalogPermissionResolver` používá výhradně explicitní role:

- `admin`: úplný přístup ke čtení i správě;
- `catalog_viewer`: pouze čtení;
- `catalog_manager`: čtení i správa;
- nepřihlášený uživatel a ostatní role: bez přístupu ke katalogu.

Role jsou součástí základních dat Identity. Adaptér
`NetteCatalogPermissionService` předává autentizaci a role z Nette Identity
resolveru; budoucí výměna Identity/RBAC tak nemění veřejný kontrakt Catalogu.
Tato vrstva představuje základní modulová oprávnění, nikoli úplnou ACL matici
nebo administrační správu účtů a rolí.

Admin presentery kontrolují oprávnění při každé chráněné akci a znovu při
zpracování formulářů/POST handlerů. Čtecí role může prohlížet produkty,
kategorie, atributy a matici variant; nemůže vytvářet ani upravovat záznamy,
přeřazovat kategorie, upravovat varianty nebo generovat jejich kombinace.
Navigace a akční odkazy jsou pouze UX vrstva, nikoli bezpečnostní hranice.

Zapisovací aplikační služby znovu kontrolují `canManage()`: produkty,
produktovou konfiguraci, kategorie a jejich pořadí, atributy a enum hodnoty,
generování i editaci variant, produktové vztahy a registraci produktových
obrázků. Přímé volání aplikační služby proto nemůže obejít prezentační
autorizaci.

Ověření pokrývá `CatalogPermissionResolver.phpt`,
`CategoriesPresenterAuthorizationIntegration.phpt`,
`ProductsPresenterAuthorizationIntegration.phpt`,
`VariantsPresenterAuthorizationIntegration.phpt`,
`ProductRelationManagerIntegration.phpt` a
`ProductImageRegistrationAuthorization.phpt`.


## Konfigurace zákaznických voleb produktu (F5-21)

Administrační správu poskytují veřejné kontrakty
`Api/ProductCustomerOptionReader` a `Api/ProductCustomerOptionWriter`.
Interní `ProductCustomerOptionQueryService` a
`ProductCustomerOptionService` oddělují čtení od transakčního zápisu;
databázovou implementaci zajišťuje
`Infrastructure/Persistence/DatabaseProductCustomerOptionRepository`.
Prezentační vrstva nepřistupuje přímo k databázi.

- Produkt může mít volby typu `choice` nebo `text`; každá volba má
  stabilní kód, povinnost, pořadí a lokalizované názvy pro všechny aktivní
  jazyky.
- Volba `choice` spravuje lokalizované popisky hodnot. Volba `text`
  přijímá zákaznický text nejvýše 500 znaků a nemá číselník hodnot.
- Aktualizace zachovává identitu existujících voleb a hodnot. Přidání,
  úprava a mazání se provádí přes aplikační službu a sdílenou transakci.
- Změna typu, která by odstranila existující hodnoty, je validována;
  odstranění používaných voleb/hodnot je chráněno aplikační kontrolou
  a databázovými vazbami. Konkrétní kontrola reálného použití v košíku
  bude znovu ověřena po implementaci modulu Cart (F7).
- Služba znovu kontroluje `CatalogPermission::canManage()`; oprávnění
  v presenteru ani skrytí ovládacích prvků nejsou jedinou ochranou.
- Cenové příplatky se v Catalogu nevypočítávají ani nespravují;
  patří do Pricing (F6-08). F5-21 nevyžaduje změnu databázového schématu.
- Systémové popisky administrace dodává idempotentní seed
  `2026-11-25-080000-seed-catalog-customer-options.sql`.

Integrační test `ProductCustomerOptionServiceIntegration.phpt` pokrývá
vytvoření a načtení voleb, překlady, zachování ID při úpravě, hodnoty,
pořadí, povinnost, neplatné typy a kódy, chybějící překlady a ochranu
před odstraněním odkazovaných záznamů. Resolver zákaznického výběru
nadále ověřují samostatné testy `OptionResolver`.


## Validace aktivace produktu (F5-24)

Nový produkt se ukládá jako koncept; jeho varianty vznikají až po založení. Přechod produktu do stavu `active` vyžaduje alespoň jednu aktivní variantu a právě jednu výchozí aktivní variantu. Databázový unikátní index již omezuje počet výchozích variant na nejvýše jednu; aplikační validace navíc ověřuje, že výchozí varianta je aktivní. Kontrola probíhá v `ProductWriteService`, tedy nezávisle na administračním formuláři. Veřejná výjimka `ProductActivationRequirementsNotMet` umožňuje UI zobrazit konkrétní lokalizovanou validační zprávu. Kategorie, obrázek, popis a ceny nejsou touto fází označeny jako povinné. CI (88 testů, PHPStan, coding standard a Deptrac), lokální `check.bat` i ruční ověření všech aktivačních scénářů v administraci byly úspěšné; F5-24 je dokončeno.


## Životní cyklus produktu a kopírování (F5-25)

Veřejný administrační kontrakt `Api/ProductLifecycleWriter` nabízí dvě oddělené operace: nevratnou archivaci a vytvoření nové kopie výhradně z archivovaného produktu. Aplikační pravidla zajišťuje `Application/ProductLifecycleService`, persistence `Infrastructure/Persistence/DatabaseProductLifecycleRepository`; presenter ani UI neprovádí přímé SQL.

- Archivace nastaví stav `archived`; databázový trigger deaktivuje všechny aktivní varianty. Další změna archivovaného produktu zpět na `draft` nebo `active` je odmítnuta databází i běžnou aplikační zapisovací službou.
- Archivovaný produkt zůstává dohledatelný a zachovává původní identitu i historické vazby. Fyzické mazání produktu/variant zůstává zakázané.
- Kopie vzniká jako nový koncept s novým ID produktu i variant. Nový kód produktu dostane jedinečný suffix `-copy`; SKU variant jsou nové a EAN se nepřenáší.
- Přenášejí se překlady a SEO, kategorie, typ, daňová třída, produktové a variantní atributy, osy/kombinace variant, výchozí varianta (zůstává neaktivní), zákaznické volby včetně překladů a aktuálních příplatků, aktuální ceny variant a vazby na sdílená média.
- Nastavení `track_stock` a `allow_backorder` se přenáší; `quantity_on_hand` i `quantity_reserved` začínají na nule. Cenová historie, skladové pohyby, objednávky, košíky a vztahy k jiným produktům se nikdy nekopírují.
- Originály a optimalizované deriváty médií se fyzicky neduplikují. Nový produkt získá samostatné produktové vazby na stejné sdílené `media_asset`; vazby `product_image_derivative` se zkopírují pro nové ID obrázku a sdílejí původní storage path. Fyzickou unikátnost derivátu dál vynucuje `media_asset_derivative.storage_key` v Media.
- Systémové texty archivace jsou v cs/en seedované neměnným souborem `2026-11-27-101000-seed-catalog-product-lifecycle.sql`; šest textů kopírování produktu dodává samostatný idempotentní seed `2026-11-27-103000-seed-catalog-product-copy.sql`.

Integrační test `ProductLifecycleServiceIntegration.phpt` ověřuje nevratnost archivace, deaktivaci variant, nové identity kopie, nové SKU a prázdné EAN, přenos aktuální ceny a skladové politiky při nulových stavech, absenci kopírované cenové/skladové historie a sdílení obrazového derivátu bez duplikace fyzického souboru. CI run `46077797137` prošel: 89 testů, PHPStan, coding standard a Deptrac. Uživatel potvrdil úspěšné migrace v obou databázích, `All active checks passed.` z lokálního `check.bat` a funkčnost archivace i kopírování v administraci. F5-25 je HOTOVO.
