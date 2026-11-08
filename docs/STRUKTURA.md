# ArgoMi BestShop – struktura projektu

**Stav:** schválená základní struktura  
**Revize:** 1.0 · 8. 11. 2026  
**Zdroj stromu:** autorův aktuální výpis adresářů projektu

Tento dokument popisuje strukturu, kterou projekt skutečně používá. Není to návrh alternativního stromu. Nové soubory se mají zařazovat do existujících částí podle odpovědnosti.

## 1. Kořen aplikace

```text
app/
├── Bootstrap.php
├── Core/
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
│       ├── Console/
│       └── Persistence/
│           └── OrmModel.php  # současný soubor k prověření/odstranění
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
- Ve výpisu existuje `Shared/Infrastructure/Persistence/OrmModel.php`; jeho skutečné použití a vazba na Nextras ORM jsou technický dluh k prověření. Nesmí se automaticky zaměňovat za rozhodnutí používat Nextras ORM.
- Prezentační mapování Nette se nyní opírá o `App\Presentation\*\**Presenter`. UI presenterové oblasti budou potřebovat explicitní a ověřené mapování; do té doby se nesmí předpokládat, že samotná adresářová struktura znamená funkční routování.

## 2. Odpovědnosti hlavních částí

| Část | Odpovědnost | Co sem nepatří |
|---|---|---|
| `Core` | Technické jádro aplikace, bootstrapovací a centrální aplikační mechanismy | Doménová logika konkrétního e-shop modulu |
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

## 5. Prezentační vrstvy

### UI/Admin
Administrace a její presentery, Latte šablony, formuláře, komponenty a datagridy. Oprávnění kontroluje Identity; samotné skrytí ovládacího prvku není autorizace.

### UI/Front
Zákaznický obchod: katalog, účet, košík, checkout, domovská stránka a zákaznické chybové obrazovky.

### UI/Shared
Znovupoužitelné UI komponenty, formuláře a Latte helpery pro Front i Admin. Sdílení je povoleno pouze pro prezentační prvky, nikoliv pro obchodní logiku.

### Presentation
Centrální Nette infrastruktura: error presentery, Latte extension a další frameworkové adaptéry. Není synonymem pro UI. `Presentation` zůstává samostatná složka podle skutečného stromu.

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

- Ověřit a dokončit mapování UI presenterů pro Nette.
- Ujasnit automatickou registraci DI extension modulů.
- Dokončit import výchozích systémových překladů.
- Prověřit a odstranit nepoužívaný Nextras ORM model/dependency, pokud se potvrdí, že není potřeba.
- Vynucovat hranice modulů automatickými architektonickými testy.

## 10. Historie

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Původní návrhy STRUKTURA v0.1/v0.2. |
| 8. 11. 2026 | v1.0: potvrzen skutečný strom `Core/Modules/Presentation/Shared/UI`; doplněny odpovědnosti a hranice. |
