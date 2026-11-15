# ArgoMi-BestShop – diagram databázového schématu

**Revize:** 1.0 · 15. 11. 2026  
**Autoritativní zdroj:** aktuální dopředné SQL migrace v `migrations/structures/`  
**Databáze:** MySQL 8.x / InnoDB

Tento dokument vizualizuje skutečné tabulky a cizí klíče po aplikaci všech aktuálních strukturálních migrací. Není samostatným zdrojem pravdy: při rozporu mají přednost SQL migrace. Diagram je rozdělen do modulových skupin podle vlastnictví dat popsaného v [DATABASE.md](DATABASE.md).

## 1. Přehled tabulek podle vlastníka

Schéma obsahuje **49 tabulek** a **61 cizích klíčů**. Vlastnictví označuje odpovědnost za doménová data, nikoli oprávnění jiných modulů přistupovat přímo do cizí persistence.

| Modul | Tabulek | Oblast |
|---|---:|---|
| Catalog | 21 | Produkty, kategorie, atributy, varianty, volby a produktové vztahy. Vlastní také jednotky a jejich překlady. |
| Pricing | 6 | Měny, katalog ISO 4217, ceny variant, historie cen a ceny hodnot voleb. |
| Tax | 2 | Daňové třídy a sazby. |
| Inventory | 2 | Skladové položky a pohyby. |
| Customer | 2 | Zákazníci a jejich adresy. |
| Shipping | 3 | Způsoby dopravy, překlady a ceny dopravy. |
| Payments | 2 | Platební metody a překlady. |
| Cart | 3 | Košíky, položky košíku a zvolené možnosti. |
| Orders | 5 | Objednávky, položky, snapshoty voleb, úpravy a historie stavů. |
| Translation | 2 | Jazyky a přechodná centrální tabulka systémových překladů. |
| Shared | 1 | Technická tabulka Transactional Outbox; záměrně bez FK do obchodních tabulek. |

## 2. Vztahy tabulek po modulech

Šipka vede od tabulky obsahující cizí klíč k odkazované tabulce. Popisek uvádí dvojici zdrojových a cílových sloupců ve stejném pořadí; u složených FK jsou uvedeny všechny sloupce. Vazby mezi různými subgrafy jsou záměrné a zobrazují skutečné databázové FK přes hranice vlastnických modulů.

```mermaid
flowchart LR
  subgraph Catalog["Catalog"]
    direction TB
    unit["unit"]
    unit_translation["unit_translation"]
    product["product"]
    product_translation["product_translation"]
    category["category"]
    category_translation["category_translation"]
    product_category["product_category"]
    attribute["attribute"]
    attribute_translation["attribute_translation"]
    attribute_value["attribute_value"]
    attribute_value_translation["attribute_value_translation"]
    product_variant_axis["product_variant_axis"]
    product_variant["product_variant"]
    product_attribute_value["product_attribute_value"]
    product_variant_attribute_value["product_variant_attribute_value"]
    product_option["product_option"]
    product_option_translation["product_option_translation"]
    product_option_value["product_option_value"]
    product_option_value_translation["product_option_value_translation"]
    product_relation["product_relation"]
    product_image["product_image"]
  end
  subgraph Pricing["Pricing"]
    direction TB
    currency["currency"]
    variant_price["variant_price"]
    variant_price_history["variant_price_history"]
    option_value_price["option_value_price"]
    iso4217_catalog_release["iso4217_catalog_release"]
    iso4217_currency["iso4217_currency"]
  end
  subgraph Tax["Tax"]
    direction TB
    tax_class["tax_class"]
    tax_rate["tax_rate"]
  end
  subgraph Inventory["Inventory"]
    direction TB
    stock_item["stock_item"]
    stock_movement["stock_movement"]
  end
  subgraph Customer["Customer"]
    direction TB
    customer["customer"]
    customer_address["customer_address"]
  end
  subgraph Shipping["Shipping"]
    direction TB
    shipping_method["shipping_method"]
    shipping_method_translation["shipping_method_translation"]
    shipping_method_price["shipping_method_price"]
  end
  subgraph Payments["Payments"]
    direction TB
    payment_method["payment_method"]
    payment_method_translation["payment_method_translation"]
  end
  subgraph Cart["Cart"]
    direction TB
    cart["cart"]
    cart_item["cart_item"]
    cart_item_option["cart_item_option"]
  end
  subgraph Orders["Orders"]
    direction TB
    shop_order["shop_order"]
    shop_order_item["shop_order_item"]
    shop_order_item_option["shop_order_item_option"]
    shop_order_adjustment["shop_order_adjustment"]
    shop_order_status_history["shop_order_status_history"]
  end
  subgraph Translation["Translation"]
    direction TB
    language["language"]
    translation_message["translation_message"]
  end
  subgraph Shared["Shared"]
    direction TB
    outbox_message["outbox_message"]
  end

  unit_translation -->|unit_id → id| unit
  tax_rate -->|tax_class_id → id| tax_class
  product -->|tax_class_id → id| tax_class
  product_translation -->|product_id → id| product
  category -->|parent_id → id| category
  category_translation -->|category_id → id| category
  product_category -->|product_id → id| product
  product_category -->|category_id → id| category
  attribute -->|unit_id → id| unit
  attribute_translation -->|attribute_id → id| attribute
  attribute_value -->|attribute_id → id| attribute
  attribute_value_translation -->|attribute_value_id → id| attribute_value
  product_variant_axis -->|product_id → id| product
  product_variant_axis -->|attribute_id → id| attribute
  product_variant -->|product_id → id| product
  product_attribute_value -->|product_id → id| product
  product_attribute_value -->|attribute_id → id| attribute
  product_attribute_value -->|attribute_value_id, attribute_id → id, attribute_id| attribute_value
  product_variant_attribute_value -->|product_variant_id, product_id → id, product_id| product_variant
  product_variant_attribute_value -->|product_id, attribute_id → product_id, attribute_id| product_variant_axis
  product_variant_attribute_value -->|attribute_id → id| attribute
  product_variant_attribute_value -->|attribute_value_id, attribute_id → id, attribute_id| attribute_value
  product_option -->|product_id → id| product
  product_option_translation -->|product_option_id → id| product_option
  product_option_value -->|product_option_id → id| product_option
  product_option_value_translation -->|product_option_value_id → id| product_option_value
  product_relation -->|product_id → id| product
  product_relation -->|related_product_id → id| product
  product_image -->|product_id → id| product
  product_image -->|product_variant_id, product_id → id, product_id| product_variant
  variant_price -->|product_variant_id → id| product_variant
  variant_price -->|currency → code| currency
  variant_price_history -->|product_variant_id → id| product_variant
  variant_price_history -->|currency → code| currency
  option_value_price -->|product_option_value_id → id| product_option_value
  option_value_price -->|currency → code| currency
  stock_item -->|product_variant_id → id| product_variant
  stock_movement -->|product_variant_id → id| product_variant
  customer_address -->|customer_id → id| customer
  shipping_method -->|tax_class_id → id| tax_class
  shipping_method_translation -->|shipping_method_id → id| shipping_method
  shipping_method_price -->|shipping_method_id → id| shipping_method
  shipping_method_price -->|currency → code| currency
  payment_method_translation -->|payment_method_id → id| payment_method
  cart -->|customer_id → id| customer
  cart -->|currency → code| currency
  cart -->|locale → code| language
  cart_item -->|cart_id → id| cart
  cart_item -->|product_variant_id, product_id → id, product_id| product_variant
  cart_item_option -->|cart_item_id, product_id → id, product_id| cart_item
  cart_item_option -->|product_option_id, product_id → id, product_id| product_option
  cart_item_option -->|product_option_value_id, product_option_id → id, product_option_id| product_option_value
  shop_order -->|customer_id → id| customer
  shop_order -->|currency → code| currency
  shop_order -->|locale → code| language
  shop_order_item -->|shop_order_id → id| shop_order
  shop_order_item_option -->|shop_order_item_id → id| shop_order_item
  shop_order_adjustment -->|shop_order_id → id| shop_order
  shop_order_status_history -->|shop_order_id → id| shop_order
  iso4217_currency -->|iso4217_catalog_release_id → id| iso4217_catalog_release
  currency -->|code, minor_units → code, minor_units| iso4217_currency
```

## 3. Poznámky k interpretaci

- Diagram zahrnuje také složené cizí klíče, samovazbu `category.parent_id`, obě vazby `product_relation` na `product` a vazby měny/jazyka přes přirozené kódy.
- Tabulka `currency` má složený FK `(code, minor_units)` do `iso4217_currency(code, minor_units)`; tím databáze vynucuje shodu kódu a přesnosti měny.
- `iso4217_currency.iso4217_catalog_release_id` odkazuje na verzované vydání katalogu ISO 4217.
- `outbox_message` nemá FK do obchodních tabulek. Outbox je technická integrační hranice; vazba na obchodní událost je vyjádřena stabilním názvem události a payloadem, nikoli databázovým FK.
- Absence FK v diagramu neznamená absenci aplikačního vztahu. Diagram zobrazuje pouze fyzické databázové cizí klíče, nikoli logické vazby, které jsou záměrně bez FK.

## 4. Zdrojové migrace

- `migrations/structures/2026-11-04-200000-init.sql` – počáteční schéma.
- `migrations/structures/2026-11-15-120000-transactional-outbox.sql` – `outbox_message`.
- `migrations/structures/2026-11-15-130000-iso4217-currency-catalog.sql` – katalog ISO 4217 a složený FK měny.
- `migrations/structures/2026-11-15-140000-normalize-iso4217-release-fk.sql` – přejmenování FK sloupce na `iso4217_catalog_release_id`.

Při změně schématu se diagram aktualizuje ve stejném kroku jako příslušná migrace. Nikdy neupravuj historické migrace jen kvůli změně diagramu.
