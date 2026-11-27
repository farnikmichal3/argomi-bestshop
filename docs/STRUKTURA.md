# ArgoMi-BestShop – struktura projektu

**Stav:** schválená základní struktura  
**Revize:** 4.21 · 27. 11. 2026  
**Zdroj stromu:** aktuální strom repozitáře na větvi `main`, ověřeno 27. 11. 2026

Tento dokument popisuje strukturu, kterou projekt skutečně používá. Není to návrh alternativního stromu. Nové soubory se mají zařazovat do existujících částí podle odpovědnosti.

## 1. Kořen aplikace

```text
app/
├── Bootstrap.php
├── Core/
│   ├── Logging/
│   │   ├── LoggerFactory.php
│   │   └── SensitiveDataProcessor.php
│   ├── Http/
│   │   └── SessionCookieConfigurator.php
│   ├── Module/
│   │   └── ModuleRegistry.php
│   └── RouterFactory.php
├── Modules/
│   ├── Cart/
│   ├── Catalog/
│   │   ├── Api/
│   │   │   ├── Dto/
│   │   │   │   ├── ProductRelationItem.php
│   │   │   ├── ProductRelationOption.php
│   │   │   ├── ProductRelationOrder.php
│   │   │   ├── ProductVariantEditorData.php
│   │   │   │   ├── ProductVariantMatrix.php
│   │   │   │   ├── VariantMatrixAxis.php
│   │   │   │   ├── VariantMatrixCombination.php
│   │   │   │   └── VariantMatrixValue.php
│   │   │   ├── Event/
│   │   │   ├── ProductRelationKind.php
│   │   │   ├── ProductRelationReader.php
│   │   │   ├── ProductRelationWriter.php
│   │   │   ├── ProductVariantEditorReader.php
│   │   │   ├── ProductVariantGenerator.php
│   │   │   ├── ProductVariantMatrixReader.php
│   │   │   ├── ProductVariantWriter.php
│   │   │   └── VariantMatrixTooLargeException.php
│   │   ├── Application/
│   │   │   ├── Dto/
│   │   │   │   ├── ProductDto.php
│   │   │   │   └── ProductTranslationDto.php
│   │   │   ├── ProductRepository.php
│   │   │   ├── ProductRelationRepository.php
│   │   │   ├── ProductRelationService.php
│   │   │   ├── ProductVariantGenerationRepository.php
│   │   │   ├── ProductVariantGenerationService.php
│   │   │   ├── ProductVariantWriteRepository.php
│   │   │   └── ProductVariantWriteService.php
│   │   ├── Domain/
│   │   │   ├── Attribute/
│   │   │   └── Variant/
│   │   │       └── VariantEan.php
│   │   └── Infrastructure/
│   │       ├── Api/
│   │       │   ├── ProductRelationCommandApiService.php
│   │       │   └── ProductRelationQueryApiService.php
│   │       ├── DI/
│   │       └── Persistence/
│   │           ├── DatabaseProductRepository.php
│   │           └── DatabaseProductRelationRepository.php
│   │           ├── DatabaseProductVariantGenerationRepository.php
│   │           └── DatabaseProductVariantMatrixRepository.php
│   ├── Checkout/
│   ├── Customer/
│   ├── Identity/
│   │   ├── Application/
│   │   │   ├── AdminAccountCredentials.php
│   │   │   ├── AdminAuthenticationRepository.php
│   │   │   └── AdminAuthenticator.php
│   │   ├── Infrastructure/
│   │   │   └── Console/
│   │   │       └── CreateAdminAccountCommand.php
│   │   └── README.md
│   ├── Inventory/
│   ├── Mailing/
│   ├── Media/
│   │   ├── Api/
│   │   │   ├── Dto/
│   │   │   ├── MediaAssetReader.php
│   │   │   ├── MediaAssetWriter.php
│   │   │   ├── MediaAssetContentReader.php
│   │   │   ├── MediaAssetPublicationWriter.php
│   │   │   ├── MediaPublicAssetReader.php
│   │   │   └── MediaPermission.php
│   │   ├── Application/
│   │   │   ├── MediaAssetService.php
│   │   │   ├── MediaAssetPublicationService.php
│   │   │   ├── MediaAssetContentService.php
│   │   │   └── MediaPublicAssetContentService.php
│   │   └── Infrastructure/
│   │       ├── DI/MediaExtension.php
│   │       ├── Image/InterventionMediaImageProcessor.php
│   │       ├── Persistence/DatabaseMediaAssetRepository.php
│   │       ├── Security/MediaPermissionService.php
│   │       └── Storage/FilesystemMediaFileStorage.php
│   ├── Orders/
│   ├── Payments/
│   ├── Pricing/
│   ├── Settings/
│   ├── Shipping/
│   ├── Tax/
│   └── Translation/
│       ├── Api/
│       │   ├── Dto/
│       │   │   └── ManagedLocale.php
│       │   ├── CustomMessageNotFoundException.php
│       │   ├── DefaultLanguageCannotBeDeactivatedException.php
│       │   ├── LanguageNotFoundException.php
│       │   ├── LocaleManager.php
│       │   ├── LocaleNumberFormatter.php
│       │   ├── MessageTranslationReader.php
│       │   ├── MessageTranslationWriter.php
│       │   ├── NumberFormattingException.php
│       │   ├── TranslationDomain.php
│       │   ├── TranslationMessageStatus.php
│       │   ├── TranslationDiagnostics.php
│       │   ├── TranslationPermission.php
│       │   ├── TranslatorFactory.php
│       │   ├── UnitFormatter.php
│       │   └── UnitFormattingException.php
│       ├── Application/
│       │   ├── Dto/
│       │   │   ├── MessageCatalog.php
│       │   │   └── MissingTranslation.php
│       │   ├── EntityTranslationResolver.php
│       │   ├── IcuLocaleNumberFormatter.php
│       │   ├── IcuUnitFormatter.php
│       │   ├── MessageCacheInvalidator.php
│       │   ├── MessageStore.php
│       │   ├── MissingTranslationReporter.php
│       │   └── TranslationPermissionResolver.php
│       ├── Domain/
│       │   └── MessageDomain.php
│       └── Infrastructure/
│           ├── Cache/
│           │   ├── CachedMessageStore.php
│           │   └── NetteMessageCacheInvalidator.php
│           ├── Diagnostics/
│           │   ├── MissingTranslationDiagnostics.php
│           │   └── MissingTranslationPanel.php
│           ├── DI/
│           │   └── TranslationExtension.php
│           ├── Persistence/
│           │   ├── DatabaseLocaleManager.php
│           │   ├── DatabaseMessageStore.php
│           │   ├── DatabaseMessageTranslationReader.php
│           │   └── DatabaseMessageTranslationWriter.php
│           ├── Security/
│           │   └── NetteTranslationPermissionService.php
│           └── Translation/
│               ├── DatabaseTranslator.php
│               └── DatabaseTranslatorFactory.php
├── Presentation/
│   ├── TranslatedPresenter.php
│   └── Error/
│       ├── Error4xx/
│       └── Error5xx/
├── Shared/
│   ├── Application/
│   │   ├── Collection/
│   │   │   └── DtoCollection.php
│   │   ├── Event/
│   │   │   ├── DomainEventListener.php
│   │   │   └── EventDispatcher.php
│   │   ├── Outbox/
│   │   │   ├── OutboxMessage.php
│   │   │   ├── OutboxMessageHandler.php
│   │   │   └── OutboxWriter.php
│   │   └── Transaction/
│   │       └── Transaction.php
│   ├── Domain/
│   │   ├── Exception/
│   │   │   ├── DomainException.php
│   │   │   ├── InvalidInputException.php
│   │   │   └── NotFoundException.php
│   │   ├── Clock/
│   │   │   └── Clock.php
│   │   ├── Event/
│   │   │   └── DomainEvent.php
│   │   ├── Locale/
│   │   │   ├── DefaultLocaleNotFoundException.php
│   │   │   ├── InvalidLocaleException.php
│   │   │   ├── Locale.php
│   │   │   └── LocaleProvider.php
│   │   ├── Money/
│   │   │   ├── Currency.php
│   │   │   ├── CurrencyMismatchException.php
│   │   │   ├── CurrencyProvider.php
│   │   │   ├── DefaultCurrencyNotFoundException.php
│   │   │   └── Money.php
│   │   ├── Percentage/
│   │   │   ├── InvalidPercentageException.php
│   │   │   └── Percentage.php
│   │   ├── PublicId/
│   │   │   ├── InvalidPublicIdException.php
│   │   │   ├── PublicId.php
│   │   │   └── PublicIdGenerator.php
│   │   └── Quantity/
│   │       ├── InvalidQuantityException.php
│   │       └── Quantity.php
│   └── Infrastructure/
│       ├── Outbox/
│       │   ├── ClaimedOutboxMessage.php
│       │   ├── DatabaseOutboxWorker.php
│       │   └── DatabaseOutboxWriter.php
│       ├── Event/
│       │   └── SynchronousEventDispatcher.php
│       ├── Transaction/
│       │   └── NetteTransaction.php
│       ├── Clock/
│       │   ├── FrozenClock.php
│       │   └── SystemClock.php
│       ├── Console/
│       │   ├── DbResetCommand.php
│       │   ├── DbResetEnvironmentPolicy.php
│       │   └── OutboxWorkerCommand.php
│       ├── PublicId/
│       │   └── SymfonyUlidGenerator.php
│       └── Persistence/
│           ├── Locale/
│           │   └── DatabaseLocaleProvider.php
│           └── Money/
│               └── DatabaseCurrencyProvider.php
└── UI/
    ├── Admin/
    │   ├── @layout.latte
    │   ├── README.md
    │   ├── Catalog/
    │   ├── Customers/
    │   ├── Dashboard/
    │   ├── DataGrid/
    │   │   ├── AdminDataGridFactory.php
    │   │   └── AdminDataGridTranslator.php
    │   ├── AdminAccessTrait.php
    │   ├── Identity/
    │   │   └── Login/
    │   │       ├── LoginPresenter.php
    │   │       └── default.latte
    │   ├── Orders/
    │   ├── Media/
    │   │   ├── MediaPresenter.php
    │   │   ├── MediaBinaryResponse.php
    │   │   └── default.latte
    │   ├── Products/
    │   │   ├── Variant/
    │   │   │   ├── VariantPresenter.php
    │   │   │   └── edit.latte
    │   │   ├── ProductAdminDataGridDataSource.php
    │   │   ├── ProductAdminDataGridFactory.php
    │   │   ├── ProductsPresenter.php
    │   │   ├── Variants/
    │   │   │   └── VariantsPresenter.php
    │   │   ├── create.latte
    │   │   ├── edit.latte
    │   │   ├── default.latte
    │   │   └── variants.latte
    │   ├── Settings/
    │   └── Translations/
    │       ├── TranslationsPresenter.php
    │       └── default.latte
    ├── Front/
    │   ├── @layout.latte
    │   ├── Account/
    │   ├── Cart/
    │   ├── Catalog/
    │   ├── Checkout/
    │   ├── Error/
    │   ├── Home/
    │   └── Media/
    │       ├── MediaPresenter.php
    │       └── PublicMediaResponse.php
    └── Shared/
        ├── Component/
        ├── Form/
        └── Latte/
```

F5-18 doplňuje veřejné kontrakty `ProductVariantEditorReader` a `ProductVariantWriter`, DTO `ProductVariantEditorData`, doménový validátor `VariantEan`, transakční `ProductVariantWriteService`, databázový adaptér a administrační `UI/Admin/Products/Variant/VariantPresenter` s šablonou `edit.latte`. EAN-8/EAN-13 je volitelný a unikátní; hmotnost je v celých gramech; výchozí varianta musí být aktivní.

F5-17 doplňuje veřejný read kontrakt `Catalog/Api/ProductVariantMatrixReader`, DTO matice, kontrakt `ProductVariantGenerator`, transakční `ProductVariantGenerationService`, dva databázové adaptéry a administrační `UI/Admin/Products/Variants/VariantsPresenter` s šablonou `UI/Admin/Products/variants.latte`. Generování používá existující `VariantCombinationHash`; databázové schéma se nemění. Testy jsou `ProductVariantMatrixIntegration.phpt` a `ProductVariantGenerationIntegration.phpt`.

Poznámky:
- `Shared/Domain/Exception` obsahuje společnou hierarchii výjimek: abstraktní `DomainException` navazuje na nativní PHP `DomainException`, `NotFoundException` označuje nenalezený doménový objekt a `InvalidInputException` zachovává dědičnost nativní `InvalidArgumentException`. Výjimky nejsou registrovány v DI; existující specializované výjimky se převádějí pouze při zachování jejich významu.
- Strom obsahuje i prázdné adresáře držené soubory `.gitkeep`.
- Modul Catalog má první čtecí implementaci produktů: aplikační kontrakt `ProductRepository`, veřejná DTO `ProductDto` a `ProductTranslationDto` a infrastrukturní `DatabaseProductRepository`. Ostatní části katalogu se budou doplňovat postupně; samotná existence adresářů neznamená implementovanou funkčnost.
- `Shared/Domain/PublicId` obsahuje neměnný hodnotový objekt `PublicId`, výjimku `InvalidPublicIdException` a rozhraní `PublicIdGenerator`. `PublicId` přijímá pouze kanonický 26znakový ULID zápis. `Shared/Infrastructure/PublicId/SymfonyUlidGenerator` generuje ULID přes `symfony/uid` 7.4; závislost na knihovně zůstává mimo doménu. Rozhraní `PublicIdGenerator` je explicitně registrováno v `config/services.neon` přes jedinou produkční implementaci. `Shared/Domain`, `Shared/Application` a `Shared/Infrastructure` jsou již vytvořené základní adresáře. `Domain/Clock/Clock` definuje společné rozhraní pro získání aktuálního okamžiku v UTC. `Infrastructure/Clock/SystemClock` je produkční implementace registrovaná v Nette DI; `FrozenClock` je explicitní testovací implementace a do produkčního DI se neregistruje. `Domain/Money` obsahuje neměnné hodnotové objekty `Money` a `Currency`, rozhraní `CurrencyProvider` a doménové výjimky. `Domain/Quantity` obsahuje neměnný hodnotový objekt `Quantity` s pevnou přesností tří desetinných míst a výjimku `InvalidQuantityException`; množství je interně reprezentováno celočíselnými tisícinami bez použití `float`. `Domain/Percentage` obsahuje neměnný hodnotový objekt `Percentage` a výjimku `InvalidPercentageException`; sazba je reprezentována podepsaným integerem v bazických bodech, bez omezení na rozsah 0–100 % a bez použití `float`. `Domain/Locale` obsahuje neměnný hodnotový objekt `Locale`, rozhraní `LocaleProvider` a doménové výjimky; podporuje omezenou syntaxi BCP 47 a odděluje identitu jazyka od jeho aktivity. `Infrastructure/Persistence/Locale/DatabaseLocaleProvider` načítá výchozí a aktivní jazyky z tabulky `language` přes Nette Database Explorer. `Infrastructure/Persistence/Money/DatabaseCurrencyProvider` načítá výchozí a aktivní měny přes Nette Database Explorer a mapuje `ActiveRow` na `Currency` uvnitř persistence hranice. Poskytovatel je explicitně registrován v `config/services.neon`; rozhraní se autowiruje přes jedinou implementaci. Verzovaný katalog ISO 4217 je uložen v tabulkách `iso4217_catalog_release` a `iso4217_currency`; migrace `2026-11-15-130000-iso4217-currency-catalog.sql` eviduje vydání SIX z 17. 9. 2026 a 155 běžných měn s přesností 0–4. Složený cizí klíč z `currency(code, minor_units)` na katalog vynucuje existenci kódu i správnou přesnost. Aktivita zůstává samostatným obchodním nastavením. Test databázových omezení a poskytovatele je v `tests/Core/Shared/CurrencyCatalogIntegration.phpt`. `Infrastructure/Event/SynchronousEventDispatcher` dostává posluchače z Nette DI přes kompilátorový výraz `tagged(domainEventListener)`. Moduly registrují vlastní posluchače jako služby s tímto tagem; centrální dispatcher nezná jejich konkrétní třídy. `Infrastructure/Outbox/DatabaseOutboxWriter` implementuje aplikační kontrakt `Application/Outbox/OutboxWriter`. Zapisuje JSON objekt do `outbox_message` přes sdílený Nette Database Explorer, používá `Clock` a `PublicIdGenerator` a odmítá zápis mimo již aktivní PDO transakci. Vlastní transakci nezahajuje, takže Outbox záznam zůstává součástí transakce obchodní operace. Writer je explicitně registrován v `config/services.neon`. `Application/Outbox/OutboxMessage` je neměnný snímek zprávy pro handler a `OutboxMessageHandler` definuje zpracování konkrétního stabilního `eventName`; handlery musí být idempotentní podle `messageId` a registrují se DI tagem `outboxMessageHandler`. `Infrastructure/Outbox/DatabaseOutboxWorker` používá krátkou transakci `SELECT ... FOR UPDATE SKIP LOCKED`, náhodný 128bitový token a lease; handler volá až po commitu. Dokončení i retry podmiňuje shodou tokenu a platným lease. Výchozí politika je 8 pokusů, lease 300 sekund a exponenciální retry 30 až 3600 sekund; dávka má výchozí velikost 10 a maximum 100 zpráv. Worker ukládá pouze typ výjimky, nikoliv její potenciálně citlivý text. `Modules/Catalog/Application/ProductRepository` definuje interní čtecí kontrakt pro produkt a jeho překlady. Interní aplikační read-model DTO `ProductDto` a `ProductTranslationDto` jsou umístěny v `Application/Dto`, nikoliv ve veřejném `Api`: interní implementace nesmí záviset na veřejné API vrstvě. `Infrastructure/Persistence/DatabaseProductRepository` používá Nette Database Explorer, mapuje `ActiveRow` na readonly DTO a odmítá neočekávané databázové typy; `ActiveRow` neopouští persistence vrstvu. `ProductDto` uchovává časové údaje jako `DateTimeImmutable` v UTC a `ProductTranslationDto` používá společný hodnotový objekt `Locale`. Veřejné DTO pro jiné moduly budou vznikat na hranici veřejné fasády, pokud je bude potřeba vystavit. Zápis základních údajů produktu nyní zajišťují `Application/ProductWriteService` a `ProductWriteRepository` s transakční implementací `Infrastructure/Persistence/DatabaseProductWriteRepository`; veřejný administrační read kontrakt `Api/ProductEditorReader` mapuje produkt, lokalizované překlady a přiřazené kategorie na vlastní API DTO. Daňové třídy se načítají jako read-only nabídka editoru a jejich existenci při zápisu ověřuje persistence adaptér. Integrační testy čtecí vrstvy a zapisovací vrstvy jsou v `tests/Modules/Catalog/DatabaseProductRepositoryIntegration.phpt` a `tests/Modules/Catalog/ProductWriteServiceIntegration.phpt`; testy jsou fail-closed pro databázi `argomi_bestshop_test` a účet `argomi_test_app`.

`Infrastructure/Transaction/NetteTransaction` implementuje aplikační kontrakt `Application/Transaction/Transaction` přes `Nette\Database\Explorer::transaction()`. Aplikační callback nedostává databázové spojení; commit a rollback při chybě zajišťuje Nette Database. Vnořená volání sdílejí jednu transakci bez savepointů: zachycená výjimka z vnořeného callbacku sama nevrátí jeho změny zpět, ty zůstávají součástí vnější transakce. Adaptér je explicitně registrován v `config/services.neon` a rozhraní se autowiruje přes jedinou produkční implementaci. `Infrastructure/Console` obsahuje `DbResetCommand`, samostatnou `DbResetEnvironmentPolicy` a `OutboxWorkerCommand`, který spustí jednu omezenou dávku přes `outbox:work --limit=10`. Výchozí hodnota `app.environment` v `common.neon` je `null`, tedy zakázaný stav; lokální `config/local.neon` musí explicitně nastavit `parameters.app.environment: development`. Příkaz kontroluje politiku před prvním databázovým dotazem a povoluje pouze přesnou hodnotu `development`. Ostatní pojistky (název databáze, účet a dvojí potvrzení) zůstávají zachovány. Negativní testy politiky jsou v `tests/Core/Console/DbResetEnvironmentPolicy.phpt`; lokální ověření celé změny probíhá v rámci F3-33. Nepoužívaný `OrmModel.php` a přímá závislost `nextras/orm` byly odstraněny v rámci F1-37. Nextras DBAL/Migrations zůstávají pouze pro migrace a související nástroje.
- `Core/Module/ModuleRegistry.php` je explicitním seznamem modulů. `Bootstrap` při kompilaci Nette DI kontejneru používá tento seznam pro registraci modulových DI extension. `Bootstrap` hledá konvencí pojmenovanou extension `App\Modules\<Module>\Infrastructure\DI\<Module>Extension`; chybějící extension přeskočí, nalezená musí být instancovatelným potomkem `Nette\DI\CompilerExtension`. Registry je jediným zdrojem seznamu modulů; nedochází k prohledávání adresářů. Modul Translation poskytuje `Infrastructure/DI/TranslationExtension.php`, kterou Bootstrap automaticky objeví jako `module_translation`. Extension registruje `DatabaseMessageStore` jako jedinou implementaci aplikačního kontraktu `MessageStore`. `Domain/MessageDomain` bezpečně vybírá jednu z tabulek `catalog_message_translation`, `cart_message_translation` a `checkout_message_translation`; `DatabaseMessageStore` provede jeden dotaz pro doménu a locale a mapuje řádky na neměnný `Application/Dto/MessageCatalog`. `ActiveRow` neopouští persistence vrstvu. `CachedMessageStore` dekoruje tento kontrakt pomocí Nette Cache; `MessageCacheInvalidator` je veřejný aplikační kontrakt a `NetteMessageCacheInvalidator` realizuje invalidaci tagy. Fallback a formátování zůstávají samostatnými navazujícími kroky F4.
- Prezentační mapování Nette je definováno v `config/common.neon`: moduly `Front` a `Admin` používají `App\UI\Front\**Presenter` a `App\UI\Admin\**Presenter`; obecné pravidlo `*: App\Presentation\*\**Presenter` zachovává presentery v `Presentation`, zejména chybové presentery. Dvojitá hvězdička `**` odpovídá konvenci Nette Application 3.3.1. Základní routování je v `Core/RouterFactory.php`: aktivní jazykové prefixy Front i cílový jazyk přesměrování `/` se generují z databáze přes `LocaleProvider`; `cs` ani `en` nejsou v routeru pevně zakódované. Admin začíná na `/admin`. Vznikly také minimální presentery `Front:Home` a `Admin:Dashboard` s vlastními Latte šablonami.

## 2. Odpovědnosti hlavních částí

| Část | Odpovědnost | Co sem nepatří |
|---|---|---|
| `Core` | Technické jádro aplikace, bootstrapovací a centrální aplikační mechanismy včetně aplikačního logování | Doménová logika konkrétního e-shop modulu |
| `Modules` | Doménové schopnosti a jejich aplikační/infrastrukturní implementace | Prezentační vrstvy Front/Admin |
| `Presentation` | Nette prezentační infrastruktura sdílená aplikací: error presentery, Latte rozšíření a frameworkové adaptéry | Doménové use-cases a obchodní pravidla |
| `Shared` | Stabilní a skutečně průřezové doménové a aplikační kontrakty a pomůcky, například typované kolekce DTO | Obecná odkládací složka nebo kód patřící jedinému modulu |
| `UI` | Uživatelská rozhraní Front, Admin a sdílené UI komponenty/formuláře | Přímé vlastnictví doménových pravidel nebo databázových dotazů |

## 3. Moduly a jejich vlastnictví

| Modul | Odpovědnost |
|---|---|
| Cart | Košík, položky, konfigurace, životní cyklus košíku |
| Catalog | Produkty, kategorie, atributy, varianty, volby, produktové vztahy a přiřazení médií k produktům/variantám |
| Media | Knihovna mediálních souborů, originály, odvozené obrazové varianty, metadata a bezpečné úložiště |
| Checkout | Průchod pokladnou, validace objednávkových údajů a koordinace dokončení nákupu |
| Customer | Zákaznický profil, adresy a zákaznická data |
| Identity | Přihlášení a administrátorské identity. Základ obsahuje credentials DTO, repository, Nette Authenticator, login/logout UI, oddělenou session/cookie i serverové úložiště (`var/sessions/admin`, `var/sessions/public`) a CLI provisioning prvního administrátora. Kompletní ACL, 2FA, audit a správa účtů v UI zůstávají navazující prací (viz ROADMAP F8). |
| Inventory | Skladové položky, pohyby, rezervace a dostupnost |
| Mailing | E-mailové zprávy, šablony, fronta a bezpečné odesílání |
| Orders | Objednávky, položky, snapshoty, stavy a historie |
| Payments | Platební metody, integrace poskytovatelů, transakce a webhooky |
| Pricing | Cenotvorba, ceníky, měny, cenová pravidla a historie cen |
| Settings | Konfigurace konkrétní instalace/obchodu |
| Shipping | Způsoby dopravy, ceny, poskytovatelé a expedice |
| Tax | Daňové třídy, sazby a výpočty daní |
| Translation | Společná infrastruktura systémových překladů, import, fallback, cache, kontrola klíčů a správa vlastních překladů |

Vlastník modulu odpovídá za jeho doménová pravidla, aplikační případy užití, persistence a veřejné API. Společná infrastruktura nesmí přebírat doménová data jiného modulu.

## 4. Vnitřní struktura modulu

U modulů, které potřebují plnou vrstvenou strukturu, se používá:

```text
Modules/<Module>/
├── Api/
│   ├── Dto/
│   └── Event/
├── Application/
├── Domain/
├── Infrastructure/
│   ├── DI/
│   └── Persistence/

```

Ne každá složka musí existovat hned. Vytváří se tehdy, když obsahuje skutečný kód. Výjimky jako Cart s jednoduchým počátečním stromem se rozvíjejí postupně.

- `Api`: veřejné fasády, DTO a události určené ostatním modulům.
- `Application`: use-cases a koordinace doménových operací.
- `Domain`: entity, value objects a pravidla.
- `Infrastructure/Persistence`: Nette Database Explorer, repozitáře, mapování ActiveRow.
- `Infrastructure/DI`: DI extension a registrace služeb modulu.
- Překladové hodnoty se v modulech neukládají do souborů. Všechny hodnoty, včetně systémových výchozích textů, jsou výhradně v databázi. Moduly v kódu používají pouze stabilní klíče.

### Core/Logging
Centrální aplikační logování přes Monolog. `LoggerFactory` vytváří JSON logger s denní rotací a limitem 30 souborů; soubory dostávají oprávnění `0600`. Limit 30 souborů je výchozí provozní nastavení, nikoliv konečné právní retenční pravidlo; jeho potvrzení nebo změna zůstává součástí F0-49. `SensitiveDataProcessor` před zápisem rekurzivně maskuje známé citlivé klíče, vybrané tokenové vzory a bezpečně redukuje objekty výjimek. Není obecným detektorem PII ve volném textu. Volající proto předává pouze explicitně povolený, minimální a bezpečný kontext; celé objekty, requesty/response, surové platební payloady a osobní údaje se nesmějí logovat. Texty výjimek jsou bezpečné a nesmějí obsahovat tajné ani osobní údaje. Logy se ukládají do kořenového adresáře `log/`, mimo veřejný `www/` webroot. Tracy zůstává samostatnou diagnostickou vrstvou.

### Shared/Application/Event a Transactional Outbox

#### Synchronní doménové události

`DomainEvent` popisuje skutečnost, která již nastala. Konkrétní typy událostí a jejich význam vlastní příslušný modul; společné rozhraní ve `Shared` pouze umožňuje předání události bez znalosti konkrétního modulu.

`EventDispatcher` a `SynchronousEventDispatcher` slouží výhradně pro kritické interní reakce, které musí být dokončeny jako součást právě prováděného aplikačního případu užití. Nejsou frontou, neukládají události a neposkytují asynchronní doručení, opakování ani transakční správu. Dispatcher volá podporující posluchače synchronně v registrovaném pořadí a výjimky záměrně nepolyká.

Aplikační use-case vlastní transakční hranici prostřednictvím `Transaction::run()`. Pokud synchronní posluchač mění databázový stav, který musí být atomický se zdrojovou obchodní změnou, musí být dispatch proveden uvnitř této transakce a nad stejným databázovým připojením. Dispatcher sám transakci nezačíná ani negarantuje rollback. Výjimka posluchače se musí dostat až z nejvyššího transakčního callbacku; její zachycení uvnitř callbacku znamená, že transakční adaptér nemá důvod provést rollback. Vnořené transakční volání navíc nepoužívají savepointy.

Synchronní posluchač smí provést pouze krátkou, deterministickou interní reakci potřebnou pro konzistenci daného use-case. Nesmí provádět externí ani nevratné vedlejší účinky: HTTP/API volání, odeslání e-mailu nebo push notifikace, skutečné platební operace, objednání dopravy či expedici. Nesmí ani čekat na externí systém. Tyto činnosti by mohly uspět, i když následná databázová transakce skončí rollbackem, nebo by mohly zbytečně prodlužovat transakci.

#### Integrační události a Transactional Outbox

Integrační a notifikační zprávy určené pro zpracování mimo právě běžící use-case se zapisují do Transactional Outbox. Zápis Outbox zprávy musí proběhnout ve stejné databázové transakci jako zdrojová obchodní změna. `OutboxWriter` vlastní transakci nezačíná a odmítá zápis mimo aktivní PDO transakci. Aplikační use-case proto odpovídá za to, že obě změny sdílí jednu transakční hranici. Události vztahující se k objednávce používají prefix `order.` a `OutboxWriter::record()` musí dostat explicitní `shopOrderId`; databáze ověřuje existenci objednávky, shodu `payload.orderId` s FK a odmítá demo objednávky. Skladové pohyby vyvolané objednávkou používají `reference_type = 'shop_order'`; databázové triggery je odmítají pro demo objednávky. Všechny čtyři seedované ukázkové objednávky mají `shop_order.is_demo = 1` a tento příznak nelze později změnit.

Doménová událost a Outbox zpráva nejsou automaticky totožné. Interní `DomainEvent` se nepřevádí ani neserializuje automaticky. Modul výslovně určí, zda a jaká integrační zpráva má vzniknout, zvolí její stabilní `eventName` a připraví explicitní serializovatelný payload. Tím zůstává interní model nezávislý na veřejném integračním formátu a citlivá či nadbytečná data se do zprávy nedostanou omylem.

Outbox zpracovává samostatný worker až po commitu. Doručování je nejméně jednou (at-least-once), nikoliv exactly-once: pád po provedení vedlejšího účinku, ale před potvrzením `delivered`, může způsobit opětovné zavolání handleru. Worker proto nesmí slibovat jediné provedení účinku; stabilní `messageId` zůstává při všech pokusech stejný a `attempt` označuje pořadí konkrétního pokusu.

Každý handler musí být idempotentní podle `messageId`. Při databázovém účinku musí deduplikační záznam a vlastní změna vzniknout v jedné databázové transakci: unikátní omezení nad `messageId` zabrání souběžnému nebo opakovanému provedení a záznam úspěšného zpracování se potvrdí atomicky s obchodní změnou. Při externím API se má `messageId` předat jako idempotency key, pokud jej poskytovatel podporuje. Pokud jej nepodporuje, handler musí počítat s nejednoznačným výsledkem a zajistit následné ověření či reconciliaci; pouhá kontrola před voláním externí služby závodní situaci neřeší.

Lease workeru má výchozí délku 300 sekund a automaticky se neprodlužuje. Handler i jeho externí volání proto musí mít striktní časové limity kratší než nakonfigurovaný lease, s rezervou pro potvrzení zprávy. Pokud zpracování lease překročí, jiný worker může zprávu převzít ještě během běhu původního handleru. Ochranný token zabrání starému workeru potvrdit cizí lease, ale nezruší již provedený externí účinek; idempotence handleru je proto nezbytná i při souběhu.

Worker používá řízené exponenciální opakování, maximální počet pokusů a stav `dead`. Ukládá pouze typ výjimky, nikoliv její text nebo payload. Chybějící handler je chyba konfigurace zpracovaná jako selhání zprávy; po vyčerpání pokusů skončí zpráva ve stavu `dead`. Duplicitní registrace handleru pro stejný `eventName` je odmítnuta při sestavení workeru. CLI `outbox:work` spouští pouze omezenou dávku a pro pravidelné zpracování se volá plánovačem/cronem.

Zprávy ve stavu `dead` se automaticky znovu nezařazují. Projekt zatím neposkytuje operaci pro jejich ruční opětovné zařazení; případný budoucí nástroj musí být samostatně navržen jako auditovatelný provozní zásah a nesmí mazat původní identitu zprávy ani obcházet idempotenci.

Outbox je společný mechanismus ve `Shared`; moduly vlastní své doménové události, rozhodnutí o vzniku integrační zprávy, stabilní názvy a payloady i konkrétní handlery. Persistence, aplikační kontrakty a CLI worker jsou implementovány ve `Shared`.

## 5. Prezentační vrstvy

### UI/Admin
Administrace a její presentery, Latte šablony, formuláře, komponenty a datagridy. Například `Admin:Dashboard` se mapuje na `App\UI\Admin\Dashboard\DashboardPresenter` podle pravidla `Admin: App\UI\Admin\**Presenter`. Společný `@layout.latte` je postaven na AdminLTE 4.10.2 a používá strukturu `app-wrapper`, `app-header`, `app-sidebar`, `app-main` a `app-footer`; responzivní levý sidebar ovládá AdminLTE PushMenu. Layout poskytuje flash zprávy, skip link, aktivní stav položek a jediný vstupní bod `/assets/admin.js`. Odkazy Přehled, Produkty, Kategorie a Překlady se zobrazují podle `CatalogPermission` a `TranslationPermission`; jejich serverové kontroly v presenterech zůstávají povinné. Navigace používá vlastní překladač domény `Admin`, nezávislý na doméně aktuální stránky; `html lang` se odvozuje z aktivního výchozího locale. `assets/js/admin.js` importuje AdminLTE, Bootstrap Icons a `shared.js`, která inicializuje Bootstrap, Naja a Nette Forms; layout skript vkládá právě jednou. AdminLTE CSS se bundluje přes esbuild, vlastní DataGrid/Sass styly odděleně do `admin-custom.css`. Detailní pravidla jsou v `app/UI/Admin/README.md`.
Společný `AdminAccessTrait` je v `UI/Admin/AdminAccessTrait.php`; všechny aktuální chráněné admin presentery jej používají pro serverovou kontrolu přihlášení, POST logout s CSRF a předání oprávnění navigaci. Veřejně dostupný login je samostatný `Identity:Login`: route `/admin/login` míří na `App\UI\Admin\Identity\Login\LoginPresenter` v `UI/Admin/Identity/Login/LoginPresenter.php` a šablonu `UI/Admin/Identity/Login/default.latte`. Login používá `TranslatorFactory` a výchozí locale; při již přihlášené identitě přesměruje na dashboard. Oprávnění modulů dále kontrolují jejich vlastní permission resolvery; samotné skrytí ovládacího prvku není autorizace.

### UI/Front
Zákaznický obchod: katalog, účet, košík, checkout, domovská stránka a zákaznické chybové obrazovky. Například `Front:Home` se mapuje na `App\UI\Front\Home\HomePresenter` podle pravidla `Front: App\UI\Front\**Presenter`.

### UI/Shared
Znovupoužitelné UI komponenty, formuláře a Latte helpery pro Front i Admin. Sdílení je povoleno pouze pro prezentační prvky, nikoliv pro obchodní logiku.

### Presentation
Centrální Nette infrastruktura: error presentery, Latte extension a další frameworkové adaptéry. Není synonymem pro UI. `Presentation` zůstává samostatná složka podle skutečného stromu.

### Frontend assets
Zdroje uživatelských rozhraní jsou oddělené podle oblastí `Front` a `Admin`:

```text
assets/
├── js/
│   ├── admin.js
│   ├── front.js
│   └── shared.js
└── scss/
    ├── _shared.scss
    ├── admin.scss
    └── front.scss
```

`front.js` a `admin.js` jsou samostatné vstupy; společná inicializace Bootstrapu, Naja a nette-forms je v `shared.js`. Sass vstupy `front.scss` a `admin.scss` používají společný základ `_shared.scss`. Specifické styly a skripty patří do odpovídající oblasti, průřezový kód pouze do sdílených souborů. Výstupní soubory `www/assets/front.css`, `admin.css`, `front.js` a `admin.js` a příkazy `build`/`watch` spravuje `package.json`.

Modul Translation vystavuje veřejné kontrakty pro překlad, čtení a správu vlastních překladů, jazyky a oprávnění. Prezentační vrstvy používají pouze `Api`; databázové čtení/zápis, cache, Tracy diagnostika a Nette security adaptér zůstávají v `Infrastructure`. `TranslationsPresenter` spravuje pouze vlastní zdroje, aktivaci jazyků a read-only přehled systémových textů. Runtime chybějící překlady se deduplikují, logují a v debug režimu zobrazují v Tracy panelu.

## 6. Pravidla závislostí

1. UI může volat veřejné API modulů nebo aplikační fasády; nesmí obcházet modul přímým přístupem do jeho persistence.
2. Jeden modul nesmí používat interní třídy jiného modulu. Používá pouze jeho `Api`.
3. `Domain` nesmí záviset na Nette UI, databázi ani konkrétním poskytovateli.
4. `Infrastructure` implementuje porty a technické adaptéry; doménová pravidla do ní nepatří.
5. `Shared` obsahuje pouze stabilní a skutečně průřezové prvky. Pokud prvek patří jednomu modulu, zůstává v něm.
6. Komunikace mezi moduly probíhá synchronně přes veřejné fasády/DTO nebo asynchronně přes doménové události. Kritické interní posluchače lze zpracovat synchronně v transakci; integrační a notifikační vedlejší účinky se doručují výhradně přes Transactional Outbox a worker.
7. Moduly nesmí importovat nic z `UI`.
8. Databázové cizí klíče mezi moduly jsou povoleny jen podle rozhodnutí popsaných v DATABASE; neznamenají povolení přímé aplikační závislosti.
9. Synchronní posluchač doménové události nesmí provádět externí nevratné operace; integrační události se zapisují do Outboxu ve stejné transakci jako zdrojová změna.
10. Moduly nesmějí obcházet centrální logovací politiku ani předávat do logu celé objekty, surové payloady nebo citlivé údaje.

### 6.1 Co patří do Shared a co ne

`Shared` není obecná složka pro kód, který se zrovna nehodí jinam. Je to stabilní základní vrstva, kterou mohou používat všechny moduly, aniž by Shared znalo jejich konkrétní implementaci.

**Do Shared patří pouze prvky, které splňují obě podmínky:**
- mají skutečně průřezový význam a jejich odpovědnost není vlastnictvím jediného obchodního modulu;
- jejich kontrakt je obecný, stabilní a použitelný bez znalosti konkrétního modulu.

Typicky sem patří:
- obecné doménové hodnotové objekty a typy s významem napříč obchodem, například `Money`, `Currency`, `Quantity`, `Percentage`, `Locale` a `PublicId`;
- obecné aplikační kontrakty, například `Clock`, `Transaction`, `EventDispatcher`, kontrakty Transactional Outbox a typovaná `DtoCollection<T>`;
- technické implementace těchto společných kontraktů, například systémové hodiny, transakční adaptér, databázové poskytovatele společných systémových hodnot a infrastruktura Outboxu;
- společná hierarchie výjimek, pokud vyjadřuje obecnou kategorii chyby a nepřebírá význam konkrétní domény.

**Do Shared nepatří:**
- entity, agregáty, value objecty a obchodní invarianty vlastněné konkrétním modulem;
- use-cases, aplikační fasády, služby a repozitáře řešící konkrétní obchodní schopnost;
- DTO, události, handlery a validační pravidla specifická pro jeden modul — ty zůstávají v jeho `Api`, `Application` nebo `Domain`;
- persistence a databázové modely konkrétního modulu, jeho tabulky ani mapování jeho obchodních dat;
- prezentační komponenty, formuláře, presentery, Latte helpery a pravidla Front/Admin;
- pomocné třídy přesunuté do Shared pouze kvůli pohodlnému importu nebo očekávanému budoucímu použití.

**Rozhodovací pravidla při umisťování nového kódu:**
1. Výchozím vlastníkem je konkrétní modul. Do Shared se kód přesouvá až tehdy, když je doložena jeho opakovaná potřeba napříč nezávislými moduly nebo jde o skutečně základní průřezový kontrakt.
2. Samotná podobnost dvou implementací není důvodem ke sdílení. Nejprve se ověří, zda mají stejný význam, invarianty a důvod ke změně.
3. Shared nesmí obsahovat podmínky, větvení ani konfiguraci podle názvů konkrétních modulů.
4. Obecný mechanismus může být ve Shared, ale konkrétní obsah a reakce vlastní modul. Například Shared poskytuje dispatcher a Outbox; moduly vlastní své události a handlery.
5. Pokud prvek začne vyžadovat znalost konkrétního modulu, přesune se do jeho vlastnictví nebo se jeho kontrakt zobecní bez úniku modulových detailů.

**Závislosti Shared jsou jednosměrně omezené:** kód v `App\Shared\...` smí používat pouze externí knihovny a PHP. Nesmí importovat `App\Modules\...`, `App\Core\...`, `App\Presentation\...`, `App\UI\...` ani `App\Bootstrap`. Platí to pro všechny vrstvy Shared včetně Infrastructure; použití externího adaptéru (například Nette Database nebo Symfony UID) je dovoleno, závislost na interní aplikační vrstvě nikoliv. Moduly a ostatní povolené vrstvy mohou Shared používat podle pravidel v `deptrac.yaml`.

Tato hranice je automaticky vynucována Deptrac pravidlem `Shared: [External]` a kontrolou `composer arch`. Dokumentace pravidlo vysvětluje; nenahrazuje automatickou kontrolu.

## 7. Aplikační kolekce DTO

`Shared/Application/Collection/DtoCollection.php` je společná generická kolekce pro homogenní seznamy DTO, které se předávají mezi aplikačními částmi a veřejnými API modulů.

- **Umístění:** aplikační vrstva, protože kolekce řeší předávání datových objektů. Nejde o doménovou kolekci s obchodními invarianty ani o infrastrukturní kolekci databázových řádků.
- **Typování:** PHPStan šablona `T of object` zachovává konkrétní typ položek. Konstruktor přijímá očekávaný `class-string<T>` a ověřuje každou položku za běhu, takže nesoulad není skryt pouze v PHPDoc.
- **Vstup:** `iterable` umožňuje předat pole, iterator i generátor. Při konstrukci se položky materializují do interního seznamu, čímž se zachová jejich pořadí a pozdější změna zdroje neovlivní členství kolekce.
- **Neměnnost:** po vytvoření neexistuje metoda pro přidání, odebrání nebo nahrazení položek. Kolekce je strukturálně neměnná; samotné DTO musí být neměnné samostatně, ideálně jako `final readonly class`.
- **Rozhraní:** `IteratorAggregate` podporuje `foreach`, `Countable` podporuje `count()`. Pomocné metody `isEmpty()`, `first()`, `get()` a `toArray()` mají přesné generické návratové typy. Chybějící index vyvolá `OutOfBoundsException`.
- **Záměrná omezení:** kolekce neimplementuje `ArrayAccess`, nemá mutační metody a nevynucuje obchodní pravidla jako unikátnost nebo limit počtu prvků. Taková pravidla patří do doménové kolekce vlastněné konkrétním modulem.
- **Použití:** modul definuje vlastní DTO ve své složce `Api/Dto`; kolekce je pouze typovaně obaluje. Nevytváří DTO, nezná `ActiveRow`, databázi ani Nette a neregistruje se v DI.
- **Rozšiřování:** změny veřejných metod musí zachovat generické typování a strukturální neměnnost a musí být doprovázeny funkčními testy i aktualizací `ZAMER.md`.

## 8. Databázová a prezentační hranice

- Repozitář modulu je jediným místem, kde se mapuje `ActiveRow` na typované DTO.
- Presentery a UI komponenty neobsahují SQL ani přímé dotazy přes Explorer.
- Objednávkové snapshoty nesmějí být při zobrazení zpětně závislé na aktuálním stavu katalogu.
- Tabulky a překladová data mají jasného vlastníka; viz DATABASE a TRANSLATIONS.

## 9. Přidání nového modulu – kontrolní seznam

1. Popsat odpovědnost a hranice modulu.
2. Vytvořit potřebné části Api, Domain, Application a Infrastructure.
3. Definovat veřejné DTO/fasády/události.
4. Přidat DI registraci podle zavedeného mechanismu.
5. Vlastnit vlastní tabulky a migrace; cizí moduly používat přes jejich API.
6. Zajistit databázové systémové překladové klíče a hodnoty; žádné překladové soubory.
7. Přidat oprávnění pro administrační operace.
8. Přidat unit/integration/architecture testy a PHPStan.
9. Přidat UI pouze do odpovídající oblasti.
10. Aktualizovat dokumentaci a ROADMAP.

## 10. Stav implementace modulů a otevřené práce

Adresáře modulů nejsou důkazem hotové funkčnosti. K 27. 11. 2026 je skutečný rozsah následující:

| Modul/oblast | Skutečný stav |
|---|---|
| Catalog | Rozsáhlé doménové a administrační API; kategorie, produkty, varianty, atributy, zákaznické volby, produktové vztahy a mediální vazby. Zákaznické produktové stránky nejsou dokončeny. |
| Media | Implementovaná knihovna, upload, bezpečné úložiště mimo www/, WebP deriváty, administrační oprávnění a explicitní publikování veřejných derivátů. |
| Translation | Implementovaná databázová překladová infrastruktura, cache, diagnostika a správa jazyků/vlastních textů. |
| Identity | Základ administrátorského přihlášení, oddělené session/cookies a CLI vytvoření účtu; nejde o kompletní ACL/RBAC ani správu účtů v UI. |
| Inventory | Implementované čtení dostupnosti produktu/variant; správa skladových operací a UI zůstávají další prací. |
| Tax | Minimální veřejný read kontrakt pro výběr daňové třídy; správa sazeb a pravidel zůstává F6. |
| Cart, Checkout, Customer, Mailing, Orders, Payments, Pricing, Settings, Shipping | Připravené hranice/adresáře a částečné databázové základy; plná aplikační workflow nejsou implementována. |

Veřejná část obsahuje prezentační kostru a endpoint pro čtení explicitně publikovaných médií. Kompletní zákaznický katalog, produktový detail, košík a checkout nejsou hotové; jejich stav a pořadí určuje výhradně ROADMAP.md.

Otevřené technické úkoly se záměrně neduplikují v tomto dokumentu. Autoritativní seznam je ROADMAP.md; architektonická rozhodnutí jsou v ZAMER.md a ADR. Při dokončení kroku se aktualizuje roadmapa i dotčená technická dokumentace.

## 11. Historie

| 18. 11. 2026 | v4.8: doplněn skutečný strom Admin správy překladů, veřejných kontraktů pro vlastní překlady/jazyky/oprávnění, runtime diagnostiky a Tracy panelu podle F4-07 a F4-17–F4-29. |
| 10. 11. 2026 | v1.3: aktualizován strom `Shared` podle skutečného repozitáře; odstraněna zastaralá reference na `OrmModel.php`, který byl odstraněn v F1-37. |
| 10. 11. 2026 | v1.4: doplněno a ověřeno explicitní mapování presenterů `UI/Front` a `UI/Admin` podle Nette Application 3.3.1; zachováno obecné mapování pro `Presentation`. |
| 10. 11. 2026 | v1.5: doplněny minimální presentery Front/Home a Admin/Dashboard, uvítací Latte šablony, jazykové routy `/cs` a `/en`, administrační `/admin` a přesměrování `/` na `/cs`. |
| 10. 11. 2026 | v1.6: doplněn explicitní `ModuleRegistry` a konvence automatické registrace modulových DI extension při kompilaci Nette kontejneru. |
| 10. 11. 2026 | v1.7: krátkodobě přidán souborový loader překladů; následně odstraněn po potvrzení závazného DB-only standardu. |
| 10. 11. 2026 | v1.8: potvrzeno, že veškeré překladové hodnoty včetně systémových jsou výhradně v databázi; odstraněny odkazy na souborové katalogy a loader. |

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Původní návrhy STRUKTURA v0.1/v0.2. |
| 8. 11. 2026 | v1.0: potvrzen skutečný strom `Core/Modules/Presentation/Shared/UI`; doplněny odpovědnosti a hranice. |
| 9. 11. 2026 | v1.1: opraven strom podle aktuální větve `main`, odstraněna neexistující složka Console z dokumentace a doplněn plánovaný úklid Nextras ORM podle F0-54/F1-37. |
| 9. 11. 2026 | v1.2: popsány samostatné Front/Admin asset vstupy a sdílené části JavaScriptu a Sassu podle F1-22. |

| 13. 11. 2026 | v1.9: doplněn skutečný strom `Shared/Domain/Money` a odpovědnost základních hodnotových objektů Money/Currency podle F2-02. |
| 13. 11. 2026 | v2.0: doplněno rozhraní `CurrencyProvider`, výjimka chybějící výchozí měny a databázová implementace přes Nette Database Explorer podle F2-03. |

| 13. 11. 2026 | v2.1: doplněn skutečný strom `Shared/Domain/Quantity` a popis přesného hodnotového objektu množství podle F2-04. |

| 13. 11. 2026 | v2.2: doplněn skutečný strom `Shared/Domain/Percentage` a popis hodnotového objektu procentní sazby podle F2-05. |

| 13. 11. 2026 | v2.3: doplněn `Locale`, `LocaleProvider`, databázový poskytovatel jazyků a dynamické generování jazykových rout podle aktivních záznamů v DB podle F2-06. |
| 13. 11. 2026 | v2.4: doplněno rozhraní `Clock`, produkční `SystemClock` a testovací `FrozenClock`; všechny implementace vracejí čas v UTC podle F2-07. |

| 14. 11. 2026 | v2.7: doplněna doménová událost, aplikační dispatcher a kontrakt posluchače; zaznamenána synchronní implementace a její registrace v DI podle F2-10. |

| 14. 11. 2026 | v2.8: doplněna automatická registrace posluchačů doménových událostí přes Nette DI tag `domainEventListener`; dispatcher dostává seznam při kompilaci kontejneru podle F2-11. |
| 14. 11. 2026 | v3.1: dokončena dokumentace a implementace `DtoCollection<T>`; opraveno číslování navazujících kapitol. |
| 15. 11. 2026 | v3.2: doplněna závazná pravidla pro kritické synchronní události, Transactional Outbox, bezpečný logovací kontext a hranice modulů. |
| 14. 11. 2026 | v3.0: doplněna aplikační generická `DtoCollection<T>`, její umístění, typová kontrola, strukturální neměnnost, veřejné operace a pravidla rozšiřování podle F2-13. |

| 15. 11. 2026 | v3.6: doplněn verzovaný katalog ISO 4217, jeho databázové vynucení přesnosti a integrační test podle F2-03/F2-17. |
| 15. 11. 2026 | v3.7: popsána závazná kritéria pro umístění kódu do Shared, příklady povoleného a zakázaného obsahu a přesná hranice závislostí podle F2-16. |
| 15. 11. 2026 | v3.8: zpřesněno oddělení synchronních interních doménových událostí od integračních zpráv Outboxu, transakční odpovědnost use-case, propagace výjimek a zákaz externích vedlejších účinků v synchronních posluchačích podle F2-18. |
| 16. 11. 2026 | v4.1: doplněna skutečná struktura modulu Translation a jeho konvenčně registrovaná DI extension podle F4-01. |
| 16. 11. 2026 | v4.2: doplněn aplikační kontrakt `MessageStore`, doménový výběr systémových textů, neměnný DTO katalog a databázový adaptér podle F4-02. |


| 17. 11. 2026 | v4.6: doplněno veřejné API modulu Translation a jednosměrná hranice závislostí; prezentační vrstvy neznají interní implementaci podle F4-08. |
| 16. 11. 2026 | v4.4: potvrzena implementace persistentní cache katalogů v modulu Translation, dekorátor `CachedMessageStore` a `NetteMessageCacheInvalidator` včetně přesné invalidace kombinace domény a locale podle F4-03. |
