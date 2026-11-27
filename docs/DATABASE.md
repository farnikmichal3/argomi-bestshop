# ArgoMi-BestShop – databáze

**Stav:** dokumentace aktuálního schématu a dopředných migrací  
**Revize:** 2.7 · 28. 11. 2026  
**Databáze:** MySQL 8.x / InnoDB / utf8mb4

Tento dokument popisuje SQL migrace, které jsou skutečně v repozitáři. Nepředstírá, že budoucí moduly nebo plánované funkce už mají hotové tabulky.

## 1. Autoritativní migrační soubory

V aktuálním repozitáři je **60 SQL migračních souborů**:

| Skupina | Počet | Význam |
|---|---:|---|
| \`structures\` | 22 | Počáteční schéma a všechny dopředné strukturální změny |
| \`basic-data\` | 2 | Základní číselníky a referenční data |
| \`dummy-data\` | 36 | Demo obsah a verzované systémové překlady pro UI |

### Structures (22)

\`\`\`text
migrations/structures/
2026-11-04-200000-init.sql
2026-11-15-120000-transactional-outbox.sql
2026-11-15-130000-iso4217-currency-catalog.sql
2026-11-15-140000-normalize-iso4217-release-fk.sql
2026-11-15-150000-protect-variant-attribute-values.sql
2026-11-15-160000-add-language-foreign-keys-to-locales.sql
2026-11-15-170000-order-accounting-invariants.sql
2026-11-15-180000-archive-products-and-variants.sql
2026-11-15-210000-split-system-message-translations.sql
2026-11-15-220000-protect-demo-orders.sql
2026-11-17-150000-admin-message-translation.sql
2026-11-18-150500-catalog-image-derivatives.sql
2026-11-20-220000-product-image-original-metadata.sql
2026-11-22-060000-protect-category-deletion.sql
2026-11-22-080000-add-product-type.sql
2026-11-22-110000-admin-identity-foundation.sql
2026-11-24-080000-product-variant-ean.sql
2026-11-25-090000-media-library.sql
2026-11-25-110000-product-image-management.sql
2026-11-25-160000-media-publication.sql
2026-11-27-100000-product-archive-terminal.sql
2026-11-27-102000-product-image-derivative-reuse.sql
\`\`\`

### Basic data (2)

\`\`\`text
migrations/basic-data/
2026-11-04-201000-basic-data.sql
2026-11-22-111000-admin-identity-roles.sql
\`\`\`

### Dummy data (36)

\`\`\`text
migrations/dummy-data/
2026-11-04-202000-demo-products.sql
2026-11-15-190000-complete-demo-data.sql
2026-11-15-200000-complete-demo-short-descriptions.sql
2026-11-15-230000-classify-demo-orders.sql
2026-11-17-140000-seed-catalog-add-to-cart.sql
2026-11-18-080000-seed-admin-translation.sql
2026-11-21-120000-seed-catalog-category-admin.sql
2026-11-22-030000-seed-catalog-category-create-form.sql
2026-11-22-040000-seed-catalog-category-editor.sql
2026-11-22-050000-seed-catalog-category-ordering.sql
2026-11-22-070000-seed-catalog-category-deletion.sql
2026-11-22-090000-seed-catalog-product-admin-list.sql
2026-11-22-100000-seed-admin-datagrid.sql
2026-11-22-112000-seed-admin-login-messages.sql
2026-11-22-120000-seed-admin-layout-navigation.sql
2026-11-22-130000-seed-adminlte-navigation.sql
2026-11-24-050000-seed-catalog-product-editor.sql
2026-11-24-060000-seed-catalog-product-variants-admin.sql
2026-11-24-070000-seed-catalog-variant-matrix-limit.sql
2026-11-24-080000-seed-catalog-product-variant-editor.sql
2026-11-24-090000-seed-catalog-attribute-admin.sql
2026-11-24-100000-seed-admin-attributes-navigation.sql
2026-11-24-110000-seed-catalog-product-configuration-editor.sql
2026-11-24-120000-seed-catalog-view-permission.sql
2026-11-25-080000-seed-catalog-customer-options.sql
2026-11-25-100000-seed-admin-media-library.sql
2026-11-25-120000-seed-product-image-management.sql
2026-11-25-130000-seed-product-image-management-form.sql
2026-11-25-140000-seed-catalog-product-editor-tabs.sql
2026-11-25-150000-seed-catalog-media-picker.sql
2026-11-25-170000-seed-admin-media-publication.sql
2026-11-25-180000-seed-catalog-media-picker-translations.sql
2026-11-25-190000-seed-catalog-product-relations-admin.sql
2026-11-27-093500-seed-catalog-product-activation-validation.sql
2026-11-27-101000-seed-catalog-product-lifecycle.sql
2026-11-27-103000-seed-catalog-product-copy.sql
\`\`\`

Nextras Migrations řadí soubory **globálně podle názvu**, nikoliv po skupinách jako tři nezávislé fáze. Skupiny a závislosti jsou nastaveny v \`config/common.neon\`:
- \`basic-data\` závisí na \`structures\`,
- \`dummy-data\` závisí na \`basic-data\`.

Časové prefixy proto musí zachovat skutečné pořadí všech již aplikovaných migrací napříč skupinami. Nová migrace musí mít prefix až za poslední již aplikovanou migrací v příslušném prostředí. Již aplikované migrační soubory se neupravují; opravy se přidávají novým dopředným souborem.

## 2. Schéma v1 – tabulky

Počáteční soubor `2026-11-04-200000-init.sql` vytváří **46 tabulek**. Po všech 22 aktuálních strukturálních migracích obsahuje schéma **58 tabulek**. Rozdíl tvoří rozdělení systémových překladů a doplnění Outboxu, katalogu ISO 4217, administrační identity, derivátů obrázků, Media knihovny databázového vynucení nevratného archivovaného stavu produktu a sdílení obrazových derivátů mezi produktovými vazbami.

| Oblast | Tabulky |
|---|---|
| Jazyk a jednotky | `language`, `unit`, `unit_translation` |
| Systémové překlady | `catalog_message_translation`, `cart_message_translation`, `checkout_message_translation`, `admin_message_translation` (každá doména vlastní svou tabulku) |
| Daně | `tax_class`, `tax_rate` |
| Katalog | `product`, `product_translation`, `category`, `category_translation`, `product_category`, `attribute`, `attribute_translation`, `attribute_value`, `attribute_value_translation`, `product_variant_axis`, `product_variant`, `product_attribute_value`, `product_variant_attribute_value`, `product_option`, `product_option_translation`, `product_option_value`, `product_option_value_translation`, `product_relation`, `product_image` (vazby na Media), `product_image_derivative` |
| Média | `media_asset`, `media_asset_derivative` |
| Administrátorská identita | `admin_user`, `admin_role`, `admin_user_role` |
| Ceník a měny | `currency`, `iso4217_catalog_release`, `iso4217_currency` |
| Sdílená infrastruktura | `outbox_message` (Transactional Outbox) |
| Ceny | `variant_price`, `variant_price_history`, `option_value_price` |
| Sklad | `stock_item`, `stock_movement` |
| Zákazníci | `customer`, `customer_address` |
| Doprava | `shipping_method`, `shipping_method_translation`, `shipping_method_price` |
| Platby | `payment_method`, `payment_method_translation` |
| Košík | `cart`, `cart_item`, `cart_item_option` |
| Objednávky | `shop_order`, `shop_order_item`, `shop_order_item_option`, `shop_order_adjustment`, `shop_order_status_history` |

Počet a názvy tabulek jsou odvozené z počátečního `init.sql` a všech aktuálních strukturálních migrací. Tabulky administrátorské identity jsou doplněny dopřednou migrací; zákaznická Identity, Mailing, Settings a samostatný Checkout workflow zatím implementovány nejsou. Přítomnost připravených modulových adresářů neznamená, že jejich aplikační workflow existuje.

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

Objednávky používají explicitní `shop_order.is_demo`. Příznak se po vytvoření nemění; databázová migrace a seed označují všechny čtyři historické demo objednávky. Provozní události `order.*` musí nést `shop_order_id`; databáze odmítá demo objednávku v Outboxu i skladový pohyb s `reference_type = 'shop_order'`. Demo zákaznické notifikace a další externí operace musí být navázány na objednávkovou událost a zpracovány až po databázové kontrole. Nelze spoléhat pouze na text názvu nebo e-mailovou adresu.

## 5. Databázové konvence

- Tabulky jsou v jednotném čísle, anglicky, `snake_case`.
- Běžné tabulky používají primární klíč `id`. Katalogová tabulka, jejíž neměnný přirozený klíč pochází z autoritativního externího standardu, může mít tento přirozený klíč přímo jako primární klíč; výjimka musí být výslovně zdokumentována. Konkrétně `iso4217_currency.code` je primárním klíčem podle třípísmenného kódu ISO 4217. Přidání umělého `id` by zde nepřineslo doménovou hodnotu.
- Cizí klíče odkazující na primární klíč `id` se standardně pojmenovávají `<referenced_table>_id`; platí to i pro vazby uvnitř referenčních katalogů (např. `iso4217_currency.iso4217_catalog_release_id` → `iso4217_catalog_release.id`). Výjimky musí být explicitně zdokumentovány a pokryty integračním testem:
  - `category.parent_id` – samovazba kategorie;
  - `product_relation.related_product_id` – druhá vazba z tabulky produktových vztahů na `product.id`;
  - `currency` – obchodní sloupec odkazující na přirozený klíč `currency.code` (používá se v cenových tabulkách, košíku a objednávce);
  - `locale` – jazykový kód odkazující na přirozený klíč `language.code`; používají jej zákaznické a transakční tabulky (`customer`, `cart`, `shop_order`), překladové tabulky katalogu a jednotlivých modulů (`*_translation`) i systémové překladové tabulky (`admin_message_translation`, `cart_message_translation`, `catalog_message_translation`, `checkout_message_translation`). Povinné jazykové vazby používají `ON DELETE RESTRICT`; pouze volitelné `customer.locale` používá `ON DELETE SET NULL`.
  - `currency(code, minor_units)` → `iso4217_currency(code, minor_units)` – složená vazba, která vynucuje současnou shodu kódu měny a její přesnosti;
  - `product_variant_attribute_value(product_id, attribute_id)` → `product_variant_axis(product_id, attribute_id)` – vazba na složený unikátní klíč osy varianty; zdrojové a cílové sloupce mají shodné názvy i pořadí.
- U složených cizích klíčů, které začínají odkazem na `id`, musí první zdrojový sloupec dodržet konvenci `<referenced_table>_id`; další sloupce vyjadřují doplňující část složené identity a jejich pořadí musí odpovídat cílovým sloupcům.
- Peněžní částky jsou celočíselné minor units a vždy mají měnu.
- Množství je desetinný typ; nepoužívat float.
- Časové údaje jsou v UTC.
- Peněžní výpočty nesmí používat binární floating point.
- Hash kombinací variant a konfigurací je SHA-256, tedy 64 hexadecimálních znaků; přesná kanonizace vstupu musí být sdílená mezi PHP a SQL a testovaná.
- Změny cen se zaznamenávají do historie.
- Výchozí varianta produktu se přepíná transakčně.
- `product_variant.ean` je volitelný globálně unikátní identifikátor typu EAN-8 nebo EAN-13. Sloupec používá ASCII a binární porovnání; databázový CHECK omezuje délku a povoluje pouze číslice. Aplikační doménový validátor navíc ověřuje kontrolní číslici. Více variant může mít `NULL`, ale konkrétní EAN smí být přiřazen pouze jednou.
- `product_variant.weight_g` je volitelná nezáporná hmotnost v celých gramech (`INT UNSIGNED`). SKU zůstává globálně unikátní.
- Přepnutí `is_default` nejprve zruší dosavadní výchozí variantu daného produktu a následně nastaví novou v jedné transakci. Unikátní index nad generovaným `default_flag` vynucuje nejvýše jednu výchozí variantu produktu.
- Skladové rezervace a skutečné odpisy jsou odlišné operace.
- Objednávkové položky uchovávají historický snapshot názvů, cen, daní, voleb a adres. Historický nákup nesmí záviset na aktuálním katalogu.
- Stav objednávky je řízen stavovým automatem v aplikační vrstvě.
- FK a CHECK omezení se testují na přesné produkční verzi MySQL.

### Bezpečné skládání SQL

- Hodnoty předávané do SQL se vždy předávají jako parametrizované hodnoty; nesmějí se vkládat do SQL řetězce konkatenací ani interpolací.
- Dynamické identifikátory (názvy tabulek, sloupců nebo řadicích výrazů) nelze bezpečně předat jako běžné SQL parametry. Musí pocházet z pevného aplikačního allowlistu a při vložení do SQL se musí escapovat databázovou vrstvou.
- Pokud databázový příkaz vyžaduje dynamický SQL fragment, celý fragment musí být sestaven pouze z pevně definovaných aplikačních variant. Uživatelský vstup nesmí určovat SQL syntaxi, operátory, klauzule ani fragmenty.
- Řazení, filtrování a další dynamické části dotazů se mapují na předem definované SQL varianty; nepoužívají se přímo hodnoty dodané klientem.
- Přímé SQL je přípustné tam, kde databázová vrstva neposkytuje potřebnou operaci nebo kde je nutná specifická atomická databázová konstrukce. I v takovém případě musí být všechny hodnoty parametrizované a dynamické identifikátory validované allowlistem a escapované.
- Testy bezpečnosti SQL musí ověřovat také odmítnutí neočekávaných hodnot a typů objektů; bezpečnostní validace musí proběhnout před první destruktivní operací.

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

## 7. Systémové překlady podle vlastníka

Historickou centrální tabulku `translation_message` odstranila dopředná migrace `2026-11-15-210000-split-system-message-translations.sql`; původní migrace `init.sql` zůstává beze změny. Systémové texty jsou fyzicky oddělené do tabulek `catalog_message_translation`, `cart_message_translation`, `checkout_message_translation` a `admin_message_translation`. Poslední tabulku vytváří `2026-11-17-150000-admin-message-translation.sql` a navazující seed `2026-11-18-080000-seed-admin-translation.sql` vkládá administrační klíče pro češtinu a angličtinu.

Všechny systémové tabulky používají stejnou ochrannou strukturu: `source`, `is_customized`, `default_hash` a `is_outdated`; unikátní dvojice `(locale, message_key)` a FK na `language(code)` brání duplicitám a osiřelým jazykovým záznamům. Admin seed ukládá SHA-1 přesné výchozí znění do `default_hash` a při konfliktu zachovává celý existující řádek. Runtime čtení i zápis probíhá výhradně přes modul Translation; systémový zdroj `core` je aplikačně chráněn před změnou a odstraněním. Podrobnosti jsou v [TRANSLATIONS](TRANSLATIONS.md).

## 8. Transactional Outbox

Dopředná migrace `2026-11-15-120000-transactional-outbox.sql` přidává samostatnou tabulku `outbox_message`. Fyzicky nesdílí tabulku s budoucí obecnou databázovou frontou.

| Sloupec | Význam |
|---|---|
| `id` | Interní celočíselný primární klíč |
| `message_id` | Veřejný ULID (26 znaků), unikátní identifikátor pro idempotenci a korelaci |
| `event_name` | Stabilní název/verze typu události; objednávkové události používají prefix `order.` |\n| `shop_order_id` | Volitelný FK na objednávku; pro `order.*` povinný a demo objednávky jsou databázově odmítnuty |
| `payload` | JSON objekt s daty události; bez tajných a citlivých údajů |
| `status` | `pending`, `processing`, `delivered` nebo `dead` |
| `attempts` | Počet převzetí workerem |
| `available_at` | Nejbližší čas, kdy lze zprávu převzít nebo znovu zkusit |
| `locked_until` / `lock_token` | Lease a náhodný 128bitový lease token aktuálního workeru |
| `created_at` | Čas vytvoření záznamu |
| `delivered_at` / `dead_at` | Čas přechodu do koncového stavu |
| `last_error` | Bezpečný stručný popis poslední chyby, bez stack trace a citlivých údajů |

Časové sloupce používají DATETIME(6) a ukládají UTC. Aplikační writer musí při vložení výslovně dodat UTC hodnoty `created_at` a `available_at`; nespoléháme na `CURRENT_TIMESTAMP`, jehož výsledek u DATETIME závisí na časovém pásmu DB session. Worker porovnává termíny s `UTC_TIMESTAMP(6)`. Tabulka má unikátní klíče pro `message_id` a aktivní `lock_token`, index pro dostupné zprávy (`status`, `available_at`, `id`) a index pro vypršené lease (`status`, `locked_until`, `id`). CHECK omezení vynucují neprázdný název události, JSON objekt, konzistenci lease a vzájemnou výlučnost koncových stavů.

### Stavový model a bezpečné převzetí

- Nový záznam začíná ve stavu `pending` s `attempts = 0` a `available_at` nastaveným na aktuální UTC čas.
- Worker v krátké transakci vybírá dostupné `pending` záznamy a `processing` záznamy s vypršeným `locked_until` pomocí `SELECT ... FOR UPDATE SKIP LOCKED`. Nastaví nový `lock_token`, `locked_until` a zvýší `attempts`; transakci potvrdí před externím voláním.
- Výsledek worker zapíše pouze při shodě `lock_token` a dosud platném lease. Worker, jehož lease vypršel nebo byl nahrazen, nesmí přepsat výsledek novějšího převzetí.
- Dočasná chyba vrací zprávu do `pending` s budoucím `available_at`. Po vyčerpání konfigurovaného limitu pokusů přejde do `dead`. Úspěch přechází do `delivered`.
- Externí volání nikdy neběží uvnitř dlouho otevřené databázové transakce. Pád po úspěšném externím účinku, ale před označením zprávy jako doručené, může způsobit opakování; proto je doručení at-least-once a handler musí být idempotentní podle `message_id`.
- Záznamy se automaticky nemažou. Retence a archivace budou samostatným provozním rozhodnutím.

Zápis do `outbox_message` musí používat stejný databázový connection a stejnou nejvyšší transakci jako obchodní změna. `OutboxWriter::record()` přijímá volitelné `shopOrderId`; události `order.*` jej musí předat a `payload.orderId` musí odpovídat. Databázový FK a triggery odmítají demo objednávky při vložení i aktualizaci Outbox zprávy. Skladové triggery obdobně odmítají `stock_movement` s `reference_type = 'shop_order'` pro demo objednávku. Počáteční skladové příjmy `demo_opening_balance` tím nejsou dotčeny. Samostatný commit Outbox záznamu není povolen. Aplikační kontrakt `OutboxWriter` a produkční `DatabaseOutboxWriter` jsou implementovány. Writer používá `Clock` pro explicitní UTC čas a `PublicIdGenerator` pro ULID; payload vždy serializuje jako JSON objekt. Před vložením ověřuje `PDO::inTransaction()` a mimo aktivní transakci vyhodí `LogicException`. Vlastní transakci nezahajuje. Claim/retry worker, testovací databáze a integrační testy atomicity navazují samostatnými kroky.

## 9. Známé mezery a navazující práce

- ověřit všechny FK, CHECK a mazací strategie,
- `[HOTOVO]` vytvořit diagramy schématu podle modulů – viz [schema.md](schema.md), který zachycuje 58 tabulek a 81 fyzických FK podle aktuálních strukturálních migrací,
- doplnit testy součtů objednávek a snapshotů,
- dokončit pravidla archivace produktů/variant,
- doplnit úplné demo snapshoty a další vlny produktů,
- navrhnout rozdělení systémových překladů do doménových tabulek,
- ověřit všechny migrace na cílové verzi MySQL.

## 10. Databázové účty a oprávnění

Databáze `argomi_bestshop` používá `utf8mb4` a kolaci `utf8mb4_0900_ai_ci`. Pro lokální vývoj jsou vytvořeny dva samostatné účty, oba omezené na `localhost` a databázi `argomi_bestshop`:

| Účet | Oprávnění nad `argomi_bestshop.*` | Účel |
|---|---|---|
| `argomi_app` | `SELECT`, `INSERT`, `UPDATE`, `DELETE` | Běžný provoz aplikace; bez oprávnění měnit schéma |
| `argomi_migrator` | `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `DROP`, `INDEX`, `REFERENCES`, `CREATE ROUTINE`, `ALTER ROUTINE`, `EXECUTE`, `TRIGGER` | Spouštění databázových migrací |

Hesla jsou generována MySQL a patří pouze do lokální konfigurace/bezpečného úložiště. Nikdy se neukládají do repozitáře ani do dokumentace. Účty nemají globální oprávnění ani `GRANT OPTION`.

## 11. Testovací databáze a integrační testy

Integrační testy používají samostatnou MySQL databázi `argomi_bestshop_test`. Nesmí sdílet databázi ani přihlašovací účty s lokálním vývojem (`argomi_bestshop`) nebo produkcí.

### Oddělené účty

| Účet | Oprávnění nad `argomi_bestshop_test.*` | Účel |
|---|---|---|
| `argomi_test_app` | `SELECT`, `INSERT`, `UPDATE`, `DELETE` | Aplikační dotazy v integračních testech |
| `argomi_test_migrator` | `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `DROP`, `INDEX`, `REFERENCES`, `CREATE ROUTINE`, `ALTER ROUTINE`, `EXECUTE`, `TRIGGER` | Aplikace databázových migrací |

Oba účty se vytvářejí pouze pro hostitele `127.0.0.1`; testovací konfigurace se k MySQL připojuje přes tento host. Hesla generuj samostatně a ukládej pouze do lokální konfigurace nebo bezpečného úložiště. Následující SQL spusť pod administrátorským MySQL účtem; nahraď oba zástupné řetězce silnými vlastními hesly:

```sql
CREATE DATABASE argomi_bestshop_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

CREATE USER 'argomi_test_app'@'127.0.0.1'
    IDENTIFIED BY 'REPLACE_WITH_STRONG_TEST_APP_PASSWORD';

CREATE USER 'argomi_test_migrator'@'127.0.0.1'
    IDENTIFIED BY 'REPLACE_WITH_STRONG_TEST_MIGRATOR_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE
    ON argomi_bestshop_test.*
    TO 'argomi_test_app'@'127.0.0.1';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES,
    CREATE ROUTINE, ALTER ROUTINE, EXECUTE, TRIGGER
    ON argomi_bestshop_test.*
    TO 'argomi_test_migrator'@'127.0.0.1';
```

Účty nemají globální oprávnění ani `GRANT OPTION`. Aplikační účet nemůže měnit schéma. Migrační účet má potřebná DDL oprávnění pouze nad testovací databází.

### Lokální konfigurace

Vzor je verzovaný v `config/local.testing.neon.example`; skutečný soubor `config/local.testing.neon` je ignorovaný Gitem. V PowerShellu vytvoř kopii:

```powershell
Copy-Item config/local.testing.neon.example config/local.testing.neon
```

V kopii dosaď hesla vytvořená v předchozím kroku. Konfigurace obsahuje dvě nezávislá připojení:

- `database:` – Nette Database Explorer a aplikační integrační testy; účet `argomi_test_app`;
- `dbal:` – Nextras DBAL používaný Nextras Migrations; účet `argomi_test_migrator`.

Obě připojení musí mířit výhradně na `argomi_bestshop_test`. Hodnota `parameters.app.environment` musí být přesně `testing`. Společný testovací bootstrap navíc kontroluje skutečnou databázi a aplikačního uživatele po navázání spojení.

### Migrace testovací databáze

Pro migrace slouží samostatný vstupní bod `bin/console-testing`. Na rozdíl od běžného `bin/console` načítá výhradně `local.testing.neon` a před startem kontroluje prostředí, cílovou databázi, hostitele a jména obou vyhrazených účtů v konfiguraci. Povoluje pouze `migrations:continue` a pomocné příkazy; destruktivní `migrations:reset` není přes tento vstupní bod dostupný.

Po vytvoření databáze a konfigurace spusť z kořene projektu:

```powershell
php bin/console-testing migrations:continue
```

Konfigurace projektu má explicitně zapnuté skupiny `structures`, `basic-data` i `dummy-data`. Demo seed je součástí testovacího prostředí záměrně: katalogový integrační test ověřuje stabilní demonstrační produkt a jeho český i anglický překlad. Testovací databáze proto musí být naplněna všemi třemi skupinami.

### Spuštění testů

```powershell
composer run test
```

Případně spusť celý projektový kontrolní skript `check.bat`, který kromě testů ověřuje i další statické a architektonické kontroly.

### GitHub Actions

Workflow `.github/workflows/composer.yml` spouští integrační testy nad dočasnou službou `mysql:8.4`. V každém běhu vytvoří novou databázi `argomi_bestshop_test`, aplikační a migrační účet, vygeneruje ignorovanou lokální konfiguraci, provede dopředné migrace přes `bin/console-testing` a následně spustí testy.

Kvůli síťovému přístupu runneru k service containeru používá CI účty s hostitelským vzorem `%`. Toto nastavení platí pouze pro jednorázový MySQL kontejner daného běhu; hesla jsou testovací a databáze se po dokončení jobu zahodí. `IntegrationTestBootstrap` přijme účet `argomi_test_app@%` pouze při explicitním prostředí GitHub Actions (`GITHUB_ACTIONS=true`). Lokální konfigurace nadále používá účty omezené na `localhost` nebo `127.0.0.1`.

CI nastavuje `TEST_DATABASE_REQUIRED=1`, takže integrační testy nesmějí být přeskočeny kvůli chybějící konfiguraci nebo databázi. Selhání vytvoření DB, migrací či připojení proto ukončí workflow chybou.

### Bezpečnostní hranice

- Testovací databáze se nikdy nesmí zaměnit za vývojovou ani produkční databázi.
- Testy se připojují aplikačním účtem s DML oprávněními; migrace používají samostatný účet s omezenými DDL oprávněními.
- `tests/Support/IntegrationTestBootstrap.php` odmítne chybějící konfiguraci, pokud jsou integrační testy výslovně vyžadovány, a ověřuje prostředí, databázi i aplikační účet.
- `bin/console-testing` odmítne jinou databázi, prostředí, hostitele nebo jména účtů v konfiguraci a nepovoluje destruktivní příkaz `migrations:reset`.
- Příkaz `db:reset` zůstává zakázán v prostředí `testing`; toto pravidlo se nemění.
- Nikdy nepoužívej `migrations:reset` ani ruční mazání schématu jako náhradu za dopředné migrace. Pokud je třeba testovací databázi znovu vytvořit, proveď to samostatným, vědomým administrátorským postupem po ověření její identity.

## 12. Bezpečnost migrací a provozu

- Migrace se spouští řízeně a jejich pořadí určuje Nextras Migrations.
- Před produkční migrací musí existovat ověřená záloha databáze.
- Produkční seed nesmí bez rozmyslu přepisovat uživatelská data.
- Demo seed musí být idempotentní nebo mít jasně definované jednorázové chování.
- Zálohy a obnova jsou provozní požadavek, ne pouze příkaz pro vývojáře.

## 13. Historie

| 28. 11. 2026 | v2.7: 60 SQL migrací (22 structures, 2 basic-data, 36 dummy-data). Samostatný idempotentní seed `2026-11-27-103000-seed-catalog-product-copy.sql` doplňuje šest textů pro kopírování produktu; historický seed archivace zůstává beze změny. Produkční i testovací databáze mají dopředné migrace aplikované. Schéma zůstává na 58 tabulkách a 81 fyzických FK. |
| 27. 11. 2026 | v2.6: 59 SQL migrací (22 structures, 2 basic-data, 35 dummy-data); dopředná migrace `2026-11-27-102000-product-image-derivative-reuse.sql` odstraňuje globální unikátnost cesty z katalogové vazby derivátu. Fyzickou unikátnost nadále vlastní `media_asset_derivative.storage_key`. Schéma zůstává na 58 tabulkách a 81 fyzických FK. |
| 27. 11. 2026 | v2.5: 58 SQL migrací (21 structures, 2 basic-data, 35 dummy-data); nová dopředná migrace `2026-11-27-100000-product-archive-terminal.sql` přidává databázovou ochranu terminálního stavu archived. Schéma zůstává na 58 tabulkách a 81 fyzických FK. |
| 18. 11. 2026 | v2.2: doplněna samostatná tabulka `admin_message_translation`, cs/en seed administračních textů a jejich SHA-1 `default_hash` (F4-17/F4-24). |
| Datum | Změna |
|---|---|
| 4. 11. 2026 | Vytvořeno počáteční schéma a seed data v SQL. |
| 8. 11. 2026 | v1.0: dokumentována skutečná časově značená struktura migrací a 46 tabulek. |
| 8. 11. 2026 | v1.1: popsány oddělené databázové účty `argomi_app` a `argomi_migrator` a jejich minimální oprávnění. |
| 15. 11. 2026 | v1.2: doplněna migrace a stavový model samostatné tabulky `outbox_message` (F3-34). |
| 15. 11. 2026 | v1.3: popsán aplikační `OutboxWriter` a transakční guard produkční implementace `DatabaseOutboxWriter`. |
| 15. 11. 2026 | v1.4: upřesněna konvence primárních klíčů a úplně zdokumentovány přirozené a pojmenovací výjimky cizích klíčů (F3-08). |
| 15. 11. 2026 | Dopřednou migrací sjednocen název FK sloupce katalogu ISO 4217 na `iso4217_catalog_release_id` podle standardní konvence. |
| 15. 11. 2026 | v1.5: doplněna pravidla bezpečného skládání SQL, parametrizace hodnot a allowlistu dynamických identifikátorů (F3-14). |
| 15. 11. 2026 | v1.6: zdokumentována izolovaná testovací databáze, oddělené účty a bezpečný vstupní bod pro dopředné migrace (F3-15b). |
| 15. 11. 2026 | v1.7: popsáno povinné spuštění MySQL integračních testů nad izolovanou MySQL 8.4 službou v GitHub Actions (F3-15c). |
| 15. 11. 2026 | v1.9: doplněno explicitní označení demo objednávek a databázová ochrana Outboxu i skladových pohybů (F3-32). |
| 15. 11. 2026 | v2.0: F3-32 lokálně ověřen; obě migrace aplikovány, integrační ochrany a aktivní kontroly prošly. |
