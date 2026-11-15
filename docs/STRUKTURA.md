# ArgoMi-BestShop – struktura projektu

**Stav:** schválená základní struktura  
**Revize:** 3.4 · 15. 11. 2026  
**Zdroj stromu:** aktuální strom repozitáře na větvi `main`, ověřeno 15. 11. 2026

Tento dokument popisuje strukturu, kterou projekt skutečně používá. Není to návrh alternativního stromu. Nové soubory se mají zařazovat do existujících částí podle odpovědnosti.

## 1. Kořen aplikace

```text
app/
├── Bootstrap.php
├── Core/
│   ├── Logging/
│   │   ├── LoggerFactory.php
│   │   └── SensitiveDataProcessor.php
│   ├── Module/
│   │   └── ModuleRegistry.php
│   └── RouterFactory.php
├── Modules/
│   ├── Cart/
│   ├── Catalog/
│   ├── Checkout/
│   ├── Customer/
│   ├── Identity/
│   ├── Inventory/
│   ├── Mailing/
│   ├── Orders/
│   ├── Payments/
│   ├── Pricing/
│   ├── Settings/
│   ├── Shipping/
│   ├── Tax/
│   └── Translation/
├── Presentation/
│   ├── Accessory/
│   │   └── LatteExtension.php
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
│       │   └── DbResetEnvironmentPolicy.php
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
    │   ├── Catalog/
    │   ├── Customers/
    │   ├── Dashboard/
    │   ├── Identity/
    │   ├── Orders/
    │   ├── Settings/
    │   └── Translations/
    ├── Front/
    │   ├── @layout.latte
    │   ├── Account/
    │   ├── Cart/
    │   ├── Catalog/
    │   ├── Checkout/
    │   ├── Error/
    │   └── Home/
    └── Shared/
        ├── Component/
        ├── Form/
        └── Latte/
```

Poznámky:
- `Shared/Domain/Exception` obsahuje společnou hierarchii výjimek: abstraktní `DomainException` navazuje na nativní PHP `DomainException`, `NotFoundException` označuje nenalezený doménový objekt a `InvalidInputException` zachovává dědičnost nativní `InvalidArgumentException`. Výjimky nejsou registrovány v DI; existující specializované výjimky se převádějí pouze při zachování jejich významu.
- Strom obsahuje i prázdné adresáře držené soubory `.gitkeep`.
- Moduly kromě Catalog zatím nemusí mít vytvořené všechny vnitřní vrstvy.
- `Shared/Domain/PublicId` obsahuje neměnný hodnotový objekt `PublicId`, výjimku `InvalidPublicIdException` a rozhraní `PublicIdGenerator`. `PublicId` přijímá pouze kanonický 26znakový ULID zápis. `Shared/Infrastructure/PublicId/SymfonyUlidGenerator` generuje ULID přes `symfony/uid` 7.4; závislost na knihovně zůstává mimo doménu. Rozhraní `PublicIdGenerator` je explicitně registrováno v `config/services.neon` přes jedinou produkční implementaci. `Shared/Domain`, `Shared/Application` a `Shared/Infrastructure` jsou již vytvořené základní adresáře. `Domain/Clock/Clock` definuje společné rozhraní pro získání aktuálního okamžiku v UTC. `Infrastructure/Clock/SystemClock` je produkční implementace registrovaná v Nette DI; `FrozenClock` je explicitní testovací implementace a do produkčního DI se neregistruje. `Domain/Money` obsahuje neměnné hodnotové objekty `Money` a `Currency`, rozhraní `CurrencyProvider` a doménové výjimky. `Domain/Quantity` obsahuje neměnný hodnotový objekt `Quantity` s pevnou přesností tří desetinných míst a výjimku `InvalidQuantityException`; množství je interně reprezentováno celočíselnými tisícinami bez použití `float`. `Domain/Percentage` obsahuje neměnný hodnotový objekt `Percentage` a výjimku `InvalidPercentageException`; sazba je reprezentována podepsaným integerem v bazických bodech, bez omezení na rozsah 0–100 % a bez použití `float`. `Domain/Locale` obsahuje neměnný hodnotový objekt `Locale`, rozhraní `LocaleProvider` a doménové výjimky; podporuje omezenou syntaxi BCP 47 a odděluje identitu jazyka od jeho aktivity. `Infrastructure/Persistence/Locale/DatabaseLocaleProvider` načítá výchozí a aktivní jazyky z tabulky `language` přes Nette Database Explorer. `Infrastructure/Persistence/Money/DatabaseCurrencyProvider` načítá výchozí a aktivní měny přes Nette Database Explorer a mapuje `ActiveRow` na `Currency` uvnitř persistence hranice. Poskytovatel je explicitně registrován v `config/services.neon`; rozhraní se autowiruje přes jedinou implementaci. `Infrastructure/Event/SynchronousEventDispatcher` dostává posluchače z Nette DI přes kompilátorový výraz `tagged(domainEventListener)`. Moduly registrují vlastní posluchače jako služby s tímto tagem; centrální dispatcher nezná jejich konkrétní třídy. `Infrastructure/Outbox/DatabaseOutboxWriter` implementuje aplikační kontrakt `Application/Outbox/OutboxWriter`. Zapisuje JSON objekt do `outbox_message` přes sdílený Nette Database Explorer, používá `Clock` a `PublicIdGenerator` a odmítá zápis mimo již aktivní PDO transakci. Vlastní transakci nezahajuje, takže Outbox záznam zůstává součástí transakce obchodní operace. Writer je explicitně registrován v `config/services.neon`. `Application/Outbox/OutboxMessage` je neměnný snímek zprávy pro handler a `OutboxMessageHandler` definuje zpracování konkrétního stabilního `eventName`; handlery musí být idempotentní podle `messageId` a registrují se DI tagem `outboxMessageHandler`. `Infrastructure/Outbox/DatabaseOutboxWorker` používá krátkou transakci `SELECT ... FOR UPDATE SKIP LOCKED`, náhodný 128bitový token a lease; handler volá až po commitu. Dokončení i retry podmiňuje shodou tokenu a platným lease. Výchozí politika je 8 pokusů, lease 300 sekund a exponenciální retry 30 až 3600 sekund; dávka má výchozí velikost 10 a maximum 100 zpráv. Worker ukládá pouze typ výjimky, nikoliv její potenciálně citlivý text. `Infrastructure/Transaction/NetteTransaction` implementuje aplikační kontrakt `Application/Transaction/Transaction` přes `Nette\Database\Explorer::transaction()`. Aplikační callback nedostává databázové spojení; commit a rollback při chybě zajišťuje Nette Database. Vnořená volání sdílejí jednu transakci bez savepointů: zachycená výjimka z vnořeného callbacku sama nevrátí jeho změny zpět, ty zůstávají součástí vnější transakce. Adaptér je explicitně registrován v `config/services.neon` a rozhraní se autowiruje přes jedinou produkční implementaci. `Infrastructure/Console` obsahuje `DbResetCommand` a samostatnou `DbResetEnvironmentPolicy`. Výchozí hodnota `app.environment` v `common.neon` je `null`, tedy zakázaný stav; lokální `config/local.neon` musí explicitně nastavit `parameters.app.environment: development`. Příkaz kontroluje politiku před prvním databázovým dotazem a povoluje pouze přesnou hodnotu `development`. Ostatní pojistky (název databáze, účet a dvojí potvrzení) zůstávají zachovány. Negativní testy politiky jsou v `tests/Core/Console/DbResetEnvironmentPolicy.phpt`; lokální ověření celé změny probíhá v rámci F3-33. Nepoužívaný `OrmModel.php` a přímá závislost `nextras/orm` byly odstraněny v rámci F1-37. Nextras DBAL/Migrations zůstávají pouze pro migrace a související nástroje.
- `Core/Module/ModuleRegistry.php` je explicitním seznamem modulů. `Bootstrap` při kompilaci Nette DI kontejneru používá tento seznam pro registraci modulových DI extension. `Bootstrap` hledá konvencí pojmenovanou extension `App\Modules\<Module>\Infrastructure\DI\<Module>Extension`; chybějící extension přeskočí, nalezená musí být instancovatelným potomkem `Nette\DI\CompilerExtension`. Registry je jediným zdrojem seznamu modulů; nedochází k prohledávání adresářů.
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
| Catalog | Produkty, kategorie, atributy, varianty, volby, obrázky a produktové vztahy |
| Checkout | Průchod pokladnou, validace objednávkových údajů a koordinace dokončení nákupu |
| Customer | Zákaznický profil, adresy a zákaznická data |
| Identity | Přihlášení, administrátorské identity, role, oprávnění, bezpečnost účtů |
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
`Shared/Application/Event/EventDispatcher` a `SynchronousEventDispatcher` slouží pouze pro kritické interní posluchače. Jejich zpracování probíhá v rámci stejné aplikační databázové transakce; výjimka se propaguje a vyvolá rollback nejvyšší transakce. Posluchače nesmějí provádět nevratné externí vedlejší účinky (HTTP/API, e-mail, push, platby ani expedici).

Integrační a notifikační události se zapisují do Transactional Outbox atomicky spolu s obchodní změnou. Outbox zpracovává samostatný worker až po commitu. Doručování je nejméně jednou (at-least-once), proto zprávy a handlery vyžadují idempotenci, řízené opakování a evidenci chyb. Outbox je společný mechanismus; moduly definují pouze své typované události a handlery. Jeho přesné porty, persistence a napojení na obecnou databázovou frontu se doplní před implementací prvních externích vedlejších účinků.

## 5. Prezentační vrstvy

### UI/Admin
Administrace a její presentery, Latte šablony, formuláře, komponenty a datagridy. Například `Admin:Dashboard` se mapuje na `App\UI\Admin\Dashboard\DashboardPresenter` podle pravidla `Admin: App\UI\Admin\**Presenter`. Oprávnění kontroluje Identity; samotné skrytí ovládacího prvku není autorizace.

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

## 10. Otevřené technické úkoly

- F1-34: prosadit databázi jako jediný zdroj všech překladových hodnot; žádné souborové katalogy ani runtime souborový fallback. Viz `TRANSLATIONS.md`.
- Implementovat databázový seed/import systémových překladů bez souborových katalogů (F4).
- `[PLÁN]` F1-37: Odstranit `OrmModel.php` a přímou závislost `nextras/orm`; před změnou znovu ověřit reference, poté aktualizovat Composer lock a spustit PHPStan/testy.
- Vynucovat hranice modulů automatickými architektonickými testy.
- Dokončit rozlišení kritických synchronních událostí a integračních událostí přes Transactional Outbox.
- Doplnit centrální katalog ISO 4217 a doménové omezení přesnosti měn.
- Prosadit explicitní kontrolu aplikačního prostředí u destruktivních konzolových příkazů.

## 11. Historie

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
