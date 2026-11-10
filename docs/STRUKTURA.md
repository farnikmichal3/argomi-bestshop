# ArgoMi-BestShop – struktura projektu

**Stav:** schválená základní struktura  
**Revize:** 1.4 · 10. 11. 2026  
**Zdroj stromu:** aktuální strom repozitáře na větvi `main`, ověřeno 10. 11. 2026

Tento dokument popisuje strukturu, kterou projekt skutečně používá. Není to návrh alternativního stromu. Nové soubory se mají zařazovat do existujících částí podle odpovědnosti.

## 1. Kořen aplikace

```text
app/
├── Bootstrap.php
├── Core/
│   ├── Logging/
│   │   ├── LoggerFactory.php
│   │   └── SensitiveDataProcessor.php
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
│   ├── Domain/
│   │   └── Event/
│   └── Infrastructure/
│       └── Console/
│           └── DbResetCommand.php
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
- Strom obsahuje i prázdné adresáře držené soubory `.gitkeep`.
- Moduly kromě Catalog zatím nemusí mít vytvořené všechny vnitřní vrstvy.
- `Shared/Domain` a `Shared/Infrastructure` jsou již vytvořené základní adresáře. `Infrastructure` obsahuje bezpečný konzolový příkaz `DbResetCommand`; doménové a infrastrukturní komponenty budou přidávány postupně podle kroků F2. Nepoužívaný `OrmModel.php` a přímá závislost `nextras/orm` byly odstraněny v rámci F1-37. Nextras DBAL/Migrations zůstávají pouze pro migrace a související nástroje.
- Prezentační mapování Nette je definováno v `config/common.neon`: moduly `Front` a `Admin` používají `App\\UI\\Front\\**Presenter` a `App\\UI\\Admin\\**Presenter`; obecné pravidlo `*: App\\Presentation\\*\\**Presenter` zachovává stávající presentery v `Presentation`, zejména `Error:Error4xx` a `Error:Error5xx`. Dvojitá hvězdička `**` odpovídá konvenci Nette Application od verze 3.2.3; projekt používá 3.3.1. Mapování převádí jméno presenteru na třídu, samo však nevytváří URL routy ani implementace presenterů.

## 2. Odpovědnosti hlavních částí

| Část | Odpovědnost | Co sem nepatří |
|---|---|---|
| `Core` | Technické jádro aplikace, bootstrapovací a centrální aplikační mechanismy včetně aplikačního logování | Doménová logika konkrétního e-shop modulu |
| `Modules` | Doménové schopnosti a jejich aplikační/infrastrukturní implementace | Prezentační vrstvy Front/Admin |
| `Presentation` | Nette prezentační infrastruktura sdílená aplikací: error presentery, Latte rozšíření a frameworkové adaptéry | Doménové use-cases a obchodní pravidla |
| `Shared` | Sdílené technické/doménové primitivy, které skutečně používá více modulů | Obecná odkládací složka pro kód bez vlastníka |
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
└── Resources/
    └── translations/
```

Ne každá složka musí existovat hned. Vytváří se tehdy, když obsahuje skutečný kód. Výjimky jako Cart s jednoduchým počátečním stromem se rozvíjejí postupně.

- `Api`: veřejné fasády, DTO a události určené ostatním modulům.
- `Application`: use-cases a koordinace doménových operací.
- `Domain`: entity, value objects a pravidla.
- `Infrastructure/Persistence`: Nette Database Explorer, repozitáře, mapování ActiveRow.
- `Infrastructure/DI`: DI extension a registrace služeb modulu.
- `Resources/translations`: výchozí systémové texty modulu určené k importu do databáze.

### Core/Logging
Centrální aplikační logování přes Monolog. `LoggerFactory` vytváří JSON logger s denní rotací a limitem 30 souborů; soubory dostávají oprávnění `0600`. Limit 30 souborů je výchozí provozní nastavení, nikoliv konečné právní retenční pravidlo; jeho potvrzení nebo změna zůstává součástí F0-49. `SensitiveDataProcessor` před zápisem odstraňuje citlivé hodnoty z logovacích zpráv, kontextu a doplňkových údajů. Logy se ukládají do kořenového adresáře `log/`, mimo veřejný `www/` webroot. Tracy zůstává samostatnou diagnostickou vrstvou.

## 5. Prezentační vrstvy

### UI/Admin
Administrace a její presentery, Latte šablony, formuláře, komponenty a datagridy. Presentery modulu `Admin` se mapují přes `Admin:Presenter` na `App\\UI\\Admin\\Presenter\\PresenterPresenter` podle pravidla `Admin: App\\UI\\Admin\\**Presenter`. Oprávnění kontroluje Identity; samotné skrytí ovládacího prvku není autorizace.

### UI/Front
Zákaznický obchod: katalog, účet, košík, checkout, domovská stránka a zákaznické chybové obrazovky. Presentery modulu `Front` se mapují přes `Front:Presenter` na `App\\UI\\Front\\Presenter\\PresenterPresenter` podle pravidla `Front: App\\UI\\Front\\**Presenter`.

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
6. Komunikace mezi moduly probíhá synchronně přes veřejné fasády/DTO nebo asynchronně přes doménové události.
7. Moduly nesmí importovat nic z `UI`.
8. Databázové cizí klíče mezi moduly jsou povoleny jen podle rozhodnutí popsaných v DATABASE; neznamenají povolení přímé aplikační závislosti.

## 7. Databázová a prezentační hranice

- Repozitář modulu je jediným místem, kde se mapuje `ActiveRow` na typované DTO.
- Presentery a UI komponenty neobsahují SQL ani přímé dotazy přes Explorer.
- Objednávkové snapshoty nesmějí být při zobrazení zpětně závislé na aktuálním stavu katalogu.
- Tabulky a překladová data mají jasného vlastníka; viz DATABASE a TRANSLATIONS.

## 8. Přidání nového modulu – kontrolní seznam

1. Popsat odpovědnost a hranice modulu.
2. Vytvořit potřebné části Api, Domain, Application a Infrastructure.
3. Definovat veřejné DTO/fasády/události.
4. Přidat DI registraci podle zavedeného mechanismu.
5. Vlastnit vlastní tabulky a migrace; cizí moduly používat přes jejich API.
6. Přidat systémové texty do vlastního `Resources/translations`.
7. Přidat oprávnění pro administrační operace.
8. Přidat unit/integration/architecture testy a PHPStan.
9. Přidat UI pouze do odpovídající oblasti.
10. Aktualizovat dokumentaci a ROADMAP.

## 9. Otevřené technické úkoly

- Mapování UI presenterů pro Nette bylo ověřeno a zavedeno v F1-32; skutečné URL routy a implementace presenterů vzniknou v navazujících fázích.
- Ujasnit automatickou registraci DI extension modulů.
- Dokončit import výchozích systémových překladů.
- `[PLÁN]` F1-37: Odstranit `OrmModel.php` a přímou závislost `nextras/orm`; před změnou znovu ověřit reference, poté aktualizovat Composer lock a spustit PHPStan/testy.
- Vynucovat hranice modulů automatickými architektonickými testy.

## 10. Historie

| 10. 11. 2026 | v1.3: aktualizován strom `Shared` podle skutečného repozitáře; odstraněna zastaralá reference na `OrmModel.php`, který byl odstraněn v F1-37. |
| 10. 11. 2026 | v1.4: doplněno a ověřeno explicitní mapování presenterů `UI/Front` a `UI/Admin` podle Nette Application 3.3.1; zachováno obecné mapování pro `Presentation`. |

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Původní návrhy STRUKTURA v0.1/v0.2. |
| 8. 11. 2026 | v1.0: potvrzen skutečný strom `Core/Modules/Presentation/Shared/UI`; doplněny odpovědnosti a hranice. |
| 9. 11. 2026 | v1.1: opraven strom podle aktuální větve `main`, odstraněna neexistující složka Console z dokumentace a doplněn plánovaný úklid Nextras ORM podle F0-54/F1-37. |
| 9. 11. 2026 | v1.2: popsány samostatné Front/Admin asset vstupy a sdílené části JavaScriptu a Sassu podle F1-22. |
