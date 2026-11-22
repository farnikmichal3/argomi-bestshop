# Admin UI

Administrace používá společný Latte layout `@layout.latte`. Všechny chráněné admin presentery jej sdílejí; login zůstává samostatnou stránkou bez administrační navigace.

## Společný layout

Layout odpovídá za:

- responzivní Bootstrap navigaci a značku aplikace;
- odkazy na Přehled, Produkty, Kategorie a Překlady;
- zvýraznění aktuální sekce a `aria-current="page"`;
- zobrazení pouze těch odkazů, ke kterým má přihlášený administrátor právo čtení;
- POST formulář odhlášení s CSRF ochranou;
- Bootstrap flash zprávy a přístupný odkaz pro přeskočení na obsah;
- jediný vstupní bod pro administrační CSS a JavaScript.

Texty navigace, přístupnostní popisky a nadpis dashboardu vlastní překladová doména `Admin` v databázi. Výchozí jazyk dokumentu `html lang` se získává z `LocaleProvider`; jazyk není pevně zakódován v šabloně.

## Oprávnění navigace

`AdminAccessTrait` ověřuje přihlášení při startu presenteru a před vykreslením poskytuje layoutu:

- `canViewCatalog` z `CatalogPermission::canView()`;
- `canViewTranslations` z `TranslationPermission::canView()`;
- `adminLocaleCode` z výchozího aktivního locale.

Skrytí odkazu je pouze prezentační pomůcka. Každý cílový presenter nadále samostatně ověřuje oprávnění při každém požadavku i při zápisu.

## JavaScript a CSS

- `assets/scss/admin.scss` skládá Bootstrap/shared styly a styly DataGridu.
- `assets/js/admin.js` je jediný vstupní bod Adminu a importuje `assets/js/shared.js`.
- `shared.js` inicializuje Bootstrap, Naja a Nette Forms.
- Admin layout vkládá `/assets/admin.js` právě jednou. Šablony nesmějí znovu vkládat Bootstrap, Naja ani Nette Forms.
- Presenterové rozšíření JavaScriptu patří do Latte bloku `scripts`, který se vykreslí až po společném `admin.js`.
- Naja a Nette Forms se neinicializují znovu v jednotlivých šablonách.

Nové administrační sekce se přidávají postupně: doplní se route odkaz v layoutu, oprávnění pro jeho viditelnost, překlady cs/en a test skutečného vykreslení. Tím se zachovává jeden navigační bod a kontrola pořadí skriptů.

## Překladové klíče

Seed `migrations/dummy-data/2026-11-22-120000-seed-admin-layout-navigation.sql` dodává idempotentně české a anglické texty pro:

- `admin.layout.skip`, `admin.flash.close`;
- `admin.navigation.label`, `admin.navigation.toggle`;
- `admin.navigation.dashboard`, `admin.navigation.products`, `admin.navigation.categories`, `admin.navigation.translations`;
- `admin.dashboard.title`.

Login používá vlastní jednoduchou šablonu bez administračního layoutu a navigace.
