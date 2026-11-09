# ArgoMi-BestShop – databáze

**Stav:** dokumentace aktuálního počátečního schématu  
**Revize:** 1.1 · 8. 11. 2026  
**Databáze:** MySQL 8.x / InnoDB / utf8mb4

Tento dokument popisuje SQL migrace, které jsou skutečně v repozitáři. Nepředstírá, že budoucí moduly nebo plánované funkce už mají hotové tabulky.

## 1. Autoritativní migrační soubory

| Skupina | Soubor | Účel |
|---|---|---|
| structures | `migrations/structures/2026-11-04-200000-init.sql` | Počáteční databázové schéma |
| basic-data | `migrations/basic-data/2026-11-04-201000-basic-data.sql` | Základní číselníky a referenční data |
| dummy-data | `migrations/dummy-data/2026-11-04-202000-demo-products.sql` | Demonstrační obsah a související ukázková data |

Migrace spravuje Nextras Migrations. Skupiny a závislosti jsou nastaveny v `config/common.neon`:
- `basic-data` závisí na `structures`,
- `dummy-data` závisí na `basic-data`.

Aktuální cesta je `migrations/`, nikoliv historicky zmiňované `database/migrations/`. Aktuální pojmenování používá časové značky, nikoliv starší `001/002/003`.

## 2. Schéma v1 – tabulky

Počáteční soubor vytváří 46 tabulek:

| Oblast | Tabulky |
|---|---|
| Jazyk, měna, jednotky a překlady | `language`, `currency`, `unit`, `unit_translation`, `translation_message` |
| Daně | `tax_class`, `tax_rate` |
| Katalog | `product`, `product_translation`, `category`, `category_translation`, `product_category`, `attribute`, `attribute_translation`, `attribute_value`, `attribute_value_translation`, `product_variant_axis`, `product_variant`, `product_attribute_value`, `product_variant_attribute_value`, `product_option`, `product_option_translation`, `product_option_value`, `product_option_value_translation`, `product_relation`, `product_image` |
| Ceny | `variant_price`, `variant_price_history`, `option_value_price` |
| Sklad | `stock_item`, `stock_movement` |
| Zákazníci | `customer`, `customer_address` |
| Doprava | `shipping_method`, `shipping_method_translation`, `shipping_method_price` |
| Platby | `payment_method`, `payment_method_translation` |
| Košík | `cart`, `cart_item`, `cart_item_option` |
| Objednávky | `shop_order`, `shop_order_item`, `shop_order_item_option`, `shop_order_adjustment`, `shop_order_status_history` |

Počet a názvy tabulek jsou převzaté přímo z aktuálního `init.sql`. Tabulky Identity, Mailing, Settings a samostatný Checkout workflow zatím v tomto počátečním schématu nejsou; jejich doplnění patří do dalších fází.

## 3. Základní data

Soubor `basic-data` vkládá referenční data pro:
- jazyky,
- měny,
- jednotky a jejich překlady,
- daňové třídy a sazby.

Základní kódy projektu zahrnují češtinu a angličtinu, CZK a EUR a jednotky používané katalogem. Přesné hodnoty a identifikátory jsou definované SQL seedem, nikoliv ručně opisované v této dokumentaci.

## 4. Demo data

Soubor `dummy-data` vytváří počáteční demonstrační obsah pro:
- kategorie,
- atributy a hodnoty atributů,
- produkty a jejich překlady,
- přiřazení produktů do kategorií,
- varianty, osy a hodnoty variant,
- volby a hodnoty voleb,
- produktové vztahy,
- ceny a historii cen,
- skladové položky,
- způsoby dopravy a platby,
- zákazníky a adresy,
- ukázkové objednávky, položky, úpravy a historii stavů,
- počáteční systémové texty.

Demo data jsou záměrně součástí i produkční instalace jako demonstrační obsah. Nesmí se však zaměnit za skutečné obchodní transakce.

### Bezpečnostní pravidla demo dat

Každý demo záznam, který může vyvolat vedlejší efekt, musí být rozpoznatelný jako demo. Implementace musí zajistit:
- demo objednávka nikdy nespustí skutečnou platbu,
- demo objednávka nikdy nevytvoří skutečnou expedici ani štítek,
- demo zákazník nedostane reálný e-mail,
- testovací webhook nebo opakované zpracování nemůže proměnit demo objednávku ve skutečnou transakci,
- demo data lze bezpečně zobrazovat a vysvětlovat v administraci.

Konkrétní označovací mechanismus (např. explicitní `is_demo`/origin flag nebo vyhrazený stav) musí být navržen v příslušném modulu a otestován. Nelze spoléhat pouze na text názvu nebo e-mailovou adresu.

## 5. Databázové konvence

- Tabulky jsou v jednotném čísle, anglicky, `snake_case`.
- Tabulky používají primární klíč `id`.
- Cizí klíče se pojmenovávají `<table>_id`; výjimky musí být explicitně zdokumentovány.
- Peněžní částky jsou celočíselné minor units a vždy mají měnu.
- Množství je desetinný typ; nepoužívat float.
- Časové údaje jsou v UTC.
- Peněžní výpočty nesmí používat binární floating point.
- Hash kombinací variant a konfigurací je SHA-256, tedy 64 hexadecimálních znaků; přesná kanonizace vstupu musí být sdílená mezi PHP a SQL a testovaná.
- Změny cen se zaznamenávají do historie.
- Výchozí varianta produktu se přepíná transakčně.
- Skladové rezervace a skutečné odpisy jsou odlišné operace.
- Objednávkové položky uchovávají historický snapshot názvů, cen, daní, voleb a adres. Historický nákup nesmí záviset na aktuálním katalogu.
- Stav objednávky je řízen stavovým automatem v aplikační vrstvě.
- FK a CHECK omezení se testují na přesné produkční verzi MySQL.

## 6. Vlastnictví dat moduly

| Datová oblast | Vlastník |
|---|---|
| Produkty, kategorie, atributy, varianty a volby | Catalog |
| Ceníky a cenová historie | Pricing |
| Daňové třídy a sazby | Tax |
| Sklad a pohyby | Inventory |
| Košíky | Cart |
| Zákazníci a adresy | Customer |
| Objednávky a snapshoty | Orders |
| Způsoby dopravy a ceny dopravy | Shipping |
| Platební metody a budoucí transakce | Payments |
| Systémové překlady a překladová infrastruktura | Translation |
| Jednotky a referenční překlady jednotek | Catalog; `unit` a `unit_translation` jsou katalogová referenční data, Translation zajišťuje pouze společnou překladovou infrastrukturu |

Vlastnictví je aplikační hranice; cizí klíče v současném schématu jsou popsány podle skutečných SQL vazeb a samy o sobě neznamenají povolení přímého přístupu z cizího modulu.

## 7. Přechodný stav překladů

Současné schéma v1 obsahuje centrální tabulku `translation_message` vedle doménových překladových tabulek jako `product_translation`, `category_translation` a `unit_translation`.

Nové rozhodnutí stanoví, že systémové překlady mají být rozděleny podle domén/modulů a chráněny před úpravami administrátorem. Současná jediná `translation_message` proto není cílový stav. Přechod je popsán v [TRANSLATIONS](TRANSLATIONS.md) a musí být proveden novou dopřednou migrací. Historická migrace se nepřepisuje.

## 8. Známé mezery a navazující práce

- doplnit testovací databázi a integrační testy migrací,
- ověřit všechny FK, CHECK a mazací strategie,
- vytvořit diagramy schématu podle modulů,
- doplnit testy součtů objednávek a snapshotů,
- dokončit pravidla archivace produktů/variant,
- doplnit úplné demo snapshoty a další vlny produktů,
- doplnit explicitní bezpečné rozlišení demo objednávek,
- navrhnout rozdělení systémových překladů do doménových tabulek,
- ověřit všechny migrace na cílové verzi MySQL.

## 9. Databázové účty a oprávnění

Databáze `argomi_bestshop` používá `utf8mb4` a kolaci `utf8mb4_0900_ai_ci`. Pro lokální vývoj jsou vytvořeny dva samostatné účty, oba omezené na `localhost` a databázi `argomi_bestshop`:

| Účet | Oprávnění nad `argomi_bestshop.*` | Účel |
|---|---|---|
| `argomi_app` | `SELECT`, `INSERT`, `UPDATE`, `DELETE` | Běžný provoz aplikace; bez oprávnění měnit schéma |
| `argomi_migrator` | `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `DROP`, `INDEX`, `REFERENCES` | Spouštění databázových migrací |

Hesla jsou generována MySQL a patří pouze do lokální konfigurace/bezpečného úložiště. Nikdy se neukládají do repozitáře ani do dokumentace. Účty nemají globální oprávnění ani `GRANT OPTION`.

## 10. Bezpečnost migrací a provozu

- Migrace se spouští řízeně a jejich pořadí určuje Nextras Migrations.
- Před produkční migrací musí existovat ověřená záloha databáze.
- Produkční seed nesmí bez rozmyslu přepisovat uživatelská data.
- Demo seed musí být idempotentní nebo mít jasně definované jednorázové chování.
- Zálohy a obnova jsou provozní požadavek, ne pouze příkaz pro vývojáře.

## 11. Historie

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Vytvořeno počáteční schéma a seed data v SQL. |
| 8. 11. 2026 | v1.0: dokumentována skutečná časově značená struktura migrací a 46 tabulek. |
| 8. 11. 2026 | v1.1: popsány oddělené databázové účty `argomi_app` a `argomi_migrator` a jejich minimální oprávnění. |
