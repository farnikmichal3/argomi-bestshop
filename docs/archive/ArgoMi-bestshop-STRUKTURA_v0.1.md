# ArgoMi-bestshop – struktura projektu

Verze dokumentu 0.1 · 4. 11. 2026 · stav: **návrh ke schválení** (krok `F0-15` v ROADMAP)

Navazuje na `ArgoMi-bestshop-ZAMER.md` (kapitola 3, modulární monolit) a `ArgoMi-bestshop-ROADMAP.md`.

---

## 1. Výchozí návrh autora a moje hodnocení

**Návrh autora:** v `app/UI` jen základní rozdělení na **front** a **admin**, v `app/modules` jednotlivé části systému.

Je to dobrý základ a doporučuji ho přijmout. Proti variantě, kdy má každý modul vlastní UI, má tyto výhody:

- **Jasné bezpečnostní rozdělení.** Front a administrace mají jiné přihlášení, jinou cookie, jinou CSP a jiné layouty. Když jsou ve dvou složkách, je to vidět na první pohled.
- **UI je přirozeně „skládač“.** Domovská stránka nebo pokladna potřebují data z více modulů. Samostatná vrstva UI, která smí číst veřejná API všech modulů, je pro to přirozené místo.
- **Jednodušší pravidla pro studenty.** Dvě vrstvy (UI a moduly) a jedno pravidlo: UI smí volat jen veřejné API modulů.

**Cena, kterou je dobré znát:** jedna funkce (např. správa produktů) leží ve třech místech: doména v `Modules/Catalog`, front v `UI/Front/Catalog`, admin v `UI/Admin/Catalog`. Zmírníme to jednotným pojmenováním oblastí (stejné názvy ve všech třech místech).

### Porovnání variant

| Kritérium | A: UI zvlášť (tvůj návrh) | B: UI uvnitř modulů | C: doporučeno (A s úpravami) |
|---|---|---|---|
| Oddělení front a admin | Velmi dobré | Slabší (ve každém modulu zvlášť) | Velmi dobré |
| Soudržnost jedné funkce | Rozptýlená do tří míst | Vše pohromadě | Rozptýlená, ale jednotně pojmenovaná |
| Pravidla závislostí | Jednoduchá | Složitější (UI modulu volá UI jiných) | Jednoduchá |
| Skládání dat z více modulů | Přirozené | Nutné obcházet | Přirozené |
| Čitelnost pro studenty | Dobrá | Střední | Dobrá |
| Hlídání deptracem | Snadné | Složitější | Snadné |

### Čtyři úpravy, které navrhuji

1. **Každý modul má složku `Api`** s veřejným povrchem (fasáda, DTO, události). Ostatní moduly a UI smí importovat jen odtud. Díky tomu jde hranici automaticky hlídat.
2. **Přibude `UI/Shared`** pro to, co sdílí front i admin (formulářové továrny, základní komponenty, Latte rozšíření). Front a admin se mezi sebou nesmí importovat.
3. **Přibude malé `Shared` jádro** (`Money`, `Quantity`, `Locale`, `Clock`, ULID, události). Vzniká ve fázi `F2` roadmapy a nesmí znát žádný modul.
4. **Dva samostatné balíčky assetů** (`front` a `admin`), aby se administrace nenačítala na frontu a každý měl vlastní CSP.

Pojmenování: složky píšeme velkým písmenem (`Modules`, `UI`), protože odpovídají jmenným prostorům PSR-4 (`App\Modules\Catalog`).

---

## 2. Strom projektu

```
argomi-bestshop/
├── app/
│   ├── Bootstrap.php
│   │
│   ├── Shared/                          Sdílené jádro, nezná žádný modul
│   │   ├── Domain/                      Money, Quantity, Locale, Currency, Percentage, Ulid, Clock
│   │   │   └── Event/                   DomainEvent, EventDispatcher (rozhraní)
│   │   └── Infrastructure/              Transaction, implementace Clock, adaptér cache
│   │
│   ├── Modules/                         Jádro obchodu, jeden modul = jedna část systému
│   │   ├── Catalog/
│   │   │   ├── Api/                     JEDINÉ, co smí používat ostatní: fasáda, DTO, události
│   │   │   │   ├── CatalogFacade.php
│   │   │   │   ├── Dto/
│   │   │   │   └── Event/
│   │   │   ├── Domain/                  Entity, hodnotové objekty, pravidla, rozhraní repozitářů
│   │   │   ├── Application/             Případy užití (příkazy a dotazy), implementace fasády
│   │   │   ├── Infrastructure/
│   │   │   │   ├── Persistence/         Repozitáře nad Nette Database Explorer (ActiveRow zde končí)
│   │   │   │   └── DI/                  CatalogExtension.php
│   │   │   ├── Resources/
│   │   │   │   └── translations/        Výchozí systémové texty modulu (cs, en) pro import do DB
│   │   │   └── README.md
│   │   ├── Pricing/
│   │   ├── Tax/
│   │   ├── Inventory/
│   │   ├── Cart/
│   │   ├── Checkout/
│   │   ├── Orders/
│   │   ├── Shipping/
│   │   ├── Payments/
│   │   ├── Customer/                    Zákazníci (front)
│   │   ├── Identity/                    Administrátoři, role, oprávnění
│   │   ├── Translation/                 Překlady v databázi, jednotky, import výchozích textů
│   │   ├── Mailing/
│   │   ├── Settings/                    Jazyky, měny, nastavení obchodu
│   │   └── (později: Compliance, Vouchers, Fulfillment, Promotions, Blog, Cms, ...)
│   │
│   └── UI/                              Prezentační vrstva, smí používat jen `Api` modulů
│       ├── Front/                       Veřejný obchod
│       │   ├── @layout.latte
│       │   ├── Home/
│       │   ├── Catalog/                 Presenter, šablony Latte a komponenty pro výpis a detail
│       │   ├── Cart/
│       │   ├── Checkout/
│       │   ├── Account/
│       │   └── Error/
│       ├── Admin/                       Administrace (jiné přihlášení, cookie a CSP)
│       │   ├── @layout.latte
│       │   ├── Dashboard/
│       │   ├── Catalog/
│       │   ├── Orders/
│       │   ├── Customers/
│       │   ├── Translations/
│       │   ├── Settings/
│       │   └── Identity/
│       └── Shared/                      Společné pro front i admin
│           ├── Component/
│           ├── Form/                    Továrny formulářů
│           └── Latte/                   Filtry a rozšíření (překlad, peníze, jednotky)
│
├── assets/                              Zdroje front-endu (build do www/assets)
│   ├── scss/
│   │   ├── front.scss
│   │   └── admin.scss
│   └── js/
│       ├── front.js
│       └── admin.js
│
├── config/
│   ├── common.neon                      Obecná konfigurace
│   ├── modules.neon                     Seznam zapnutých modulů (jejich DI extension)
│   └── local.neon                       Lokální heslo a nastavení (mimo Git)
│
├── database/
│   └── migrations/
│       ├── structures/                  001_wave1_structure.sql, další migrace
│       ├── basic-data/                  002_basic_data.sql
│       └── dummy-data/                  003_demo_data.sql
│
├── tests/
│   ├── Unit/                            Odpovídá Modules/* (doména bez databáze)
│   ├── Integration/                     Repozitáře a fasády nad testovací databází
│   ├── Architecture/                    Hranice modulů a pravidla (deptrac, vlastní testy)
│   └── Fixtures/
│
├── docs/
│   ├── ZAMER.md                         (ArgoMi-bestshop-ZAMER.md)
│   ├── ROADMAP.md                       (ArgoMi-bestshop-ROADMAP.md)
│   ├── STRUKTURA.md                     (tento soubor)
│   ├── adr/                             Záznamy rozhodnutí (0001-modularni-monolit.md ...)
│   └── lessons/                         Výukové lekce
│
├── bin/                                 Konzolové příkazy (db:migrate, translations:import ...)
├── var/
│   └── private/                         Soukromé soubory mimo webroot (nahrané soubory zákazníků)
├── log/                                 Logy (mimo Git)
├── temp/                                Cache (mimo Git)
├── vendor/                              Composer (mimo Git)
├── node_modules/                        npm (mimo Git)
├── www/                                 JEDINÁ veřejná složka (document root)
│   ├── index.php
│   ├── assets/                          Sestavené CSS a JS (mimo Git)
│   └── media/                           Veřejné obrázky produktů (zpracované)
│
├── .github/workflows/                   CI
├── .editorconfig
├── .gitattributes
├── .gitignore
├── .nvmrc
├── composer.json
├── composer.lock
├── deptrac.yaml                         (nebo konfigurace phpat, po rozhodnutí F0)
├── package.json
├── package-lock.json
├── phpstan.neon
└── README.md
```

---

## 3. Pravidla závislostí

Kdo smí importovat koho. Vše, co tabulka nepovoluje, zakazuje a hlídá CI (krok `F2-15`, `F14-19`).

| Vrstva | Smí používat | Nesmí používat |
|---|---|---|
| `Shared` | jen sebe a PHP | jakýkoli modul, UI |
| `Modules/X/Domain` | `Shared`, vlastní `Domain` | Nette, databázi, `Application`, `Infrastructure`, jiné moduly |
| `Modules/X/Application` | vlastní `Domain`, `Shared`, **`Api` jiných modulů** | `Domain`, `Application` a `Infrastructure` jiných modulů, UI |
| `Modules/X/Infrastructure` | vlastní `Domain` a `Application`, `Shared`, Nette Database | jiné moduly, UI |
| `Modules/X/Api` | `Shared` (pro DTO a události) | cokoli dalšího |
| `UI/Front` | **`Api` modulů**, `Shared`, `UI/Shared` | `Domain`, `Application`, `Infrastructure` modulů, `UI/Admin` |
| `UI/Admin` | **`Api` modulů**, `Shared`, `UI/Shared` | `Domain`, `Application`, `Infrastructure` modulů, `UI/Front` |
| `UI/Shared` | `Api` modulů, `Shared` | `UI/Front`, `UI/Admin` |

Klíčová pravidla:

1. **Ven z modulu jde jen `Api`.** Všechno ostatní je soukromé.
2. **`ActiveRow` neopouští `Infrastructure/Persistence`.** Ven jdou typované `readonly` DTO.
3. **Mezi moduly se odkazuje ID, bez cizích klíčů v databázi** (viz ZAMER 6.3).
4. **Komunikace mezi moduly:** synchronně přes fasády z `Api`, asynchronně přes události.
5. **Modul nezná UI.** Žádný modul neimportuje nic z `app/UI`.

---

## 4. Co kam patří

| Co | Kam |
|---|---|
| Entita, hodnotový objekt, doménové pravidlo | `Modules/X/Domain` |
| Případ užití (např. „přidat produkt do košíku“) | `Modules/X/Application` |
| Veřejná fasáda, DTO, událost | `Modules/X/Api` |
| Repozitář nad Explorer, mapování na DTO | `Modules/X/Infrastructure/Persistence` |
| DI extension modulu | `Modules/X/Infrastructure/DI` |
| Výchozí systémové texty modulu | `Modules/X/Resources/translations` |
| Presenter, šablona Latte, komponenta pro zákazníka | `UI/Front/<Oblast>` |
| Presenter, šablona, Datagrid pro administrátora | `UI/Admin/<Oblast>` |
| Formulář, který používá front i admin | `UI/Shared/Form` |
| Latte filtr (překlad, cena, jednotka) | `UI/Shared/Latte` |
| Peníze, množství, jazyk, měna, ULID | `Shared/Domain` |
| SQL migrace | `database/migrations/<skupina>` |
| Soukromé nahrané soubory | `var/private` |
| ADR, lekce, plán | `docs/` |

---

## 5. Nový modul: kontrolní seznam

1. Složky `Api`, `Domain`, `Application`, `Infrastructure/{Persistence,DI}`, `Resources/translations` a `README.md`.
2. `XExtension` v `Infrastructure/DI` a zápis do `config/modules.neon`.
3. Fasáda a DTO v `Api`. Nic dalšího se ven neexportuje.
4. Tabulky modulu v migraci, odkazy na jiné moduly jen přes ID.
5. Překladové tabulky entit modulu a výchozí texty (cs a en).
6. Pravidla v deptracu (nebo phpat) a architektonický test.
7. Jednotkové a integrační testy, PHPStan level max.
8. Oblasti v `UI/Front` a `UI/Admin` podle potřeby.
9. Oprávnění pro administraci (modul `Identity`).
10. Záznam v dokumentaci a případné ADR.

---

## 6. Otevřené body a rizika

| Bod | Poznámka | Krok |
|---|---|---|
| Mapování presenterů | Nette hledá presentery podle mapování. Novější kostry používají složku `app/Presentation`. Naše `app/UI` s oblastmi `Front` a `Admin` vyžaduje upravit mapování a jmenný prostor. Konkrétní podobu ověříme v dokumentaci Nette Application a vyzkoušíme na malém příkladu, **než ji zafixujeme**. | `F1-32` |
| Registrace modulů | Jak načíst DI extension každého modulu ze seznamu `config/modules.neon`. | `F1-33` |
| Načítání výchozích textů | Formát souborů s překlady v modulech a jejich import do databáze. | `F1-34` |
| Dva balíčky assetů | `front` a `admin` místo jednoho `app`. Dotčeno: kroky `F1-22` a `F1-23` v roadmapě (původně `app.scss` a `app.js`). | `F1-22`, `F1-23` |
| Adresa administrace a jazyků | Prefix `/admin`, jazyk v URL (rozhodnutí o prefixu čeká). | `F0-21` |
| Jméno `Identity` | Modul pro administrátory a role. Případně ho lze pojmenovat jinak, ale musí být oddělený od `Customer`. | `F8` |
| Granularita modulů | Moduly `Pricing`, `Tax`, `Inventory` jsou malé. Zůstanou oddělené kvůli vlnám 2 až 4. Pokud se ukáže, že jejich hranice brzdí, spojíme je. | `F6` |

---

## 7. Historie změn

| Datum | Změna |
|---|---|
| 4. 11. 2026 | První verze: srovnání variant A, B a C, strom projektu, pravidla závislostí. |
