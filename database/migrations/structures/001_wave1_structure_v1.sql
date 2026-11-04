-- =============================================================================
-- ArgoMi BestShop · vlna 1 · revidované schéma v1
-- MySQL 8.0.16+ · InnoDB · utf8mb4
--
-- Aplikační invarianty:
-- * configuration_hash = SHA-256 kanonické konfigurace položky košíku.
-- * Změny variant_price se zapisují také do variant_price_history.
--   Historie sama nezajišťuje právní výpočet nejnižší ceny za 30 dní.
-- * Překryvy intervalů tax_rate a cykly category.parent_id vyžadují
--   transakční ochranu aplikační vrstvou; CHECK je neumí vynutit.
-- * quantity_on_hand smí být záporné pouze při allow_backorder = 1.
-- * U shop_order_adjustment je sleva záporná, ostatní typy nezáporné;
--   částky rozdělené podle sazeb DPH se ukládají do samostatných řádků.
-- =============================================================================

CREATE TABLE language (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(10)  NOT NULL,
    name         VARCHAR(100) NOT NULL,
    is_default   TINYINT(1) NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    default_flag TINYINT(1) GENERATED ALWAYS AS (IF(is_default = 1, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_language_code (code),
    UNIQUE KEY uq_language_default (default_flag),
    CONSTRAINT ck_language_default_is_active CHECK (is_default = 0 OR is_active = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE currency (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         CHAR(3)      NOT NULL,
    name         VARCHAR(100) NOT NULL,
    symbol       VARCHAR(8)   NOT NULL,
    minor_units  TINYINT UNSIGNED NOT NULL DEFAULT 2,
    is_default   TINYINT(1) NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    default_flag TINYINT(1) GENERATED ALWAYS AS (IF(is_default = 1, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_currency_code (code),
    UNIQUE KEY uq_currency_default (default_flag),
    CONSTRAINT ck_currency_default_is_active CHECK (is_default = 0 OR is_active = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE unit (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(20) NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_unit_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE unit_translation (
    id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_id BIGINT UNSIGNED NOT NULL,
    locale  VARCHAR(10)  NOT NULL,
    symbol  VARCHAR(20)  NOT NULL,
    name    VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_unit_translation (unit_id, locale),
    CONSTRAINT fk_unit_translation_unit FOREIGN KEY (unit_id)
        REFERENCES unit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE translation_message (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    domain        VARCHAR(50)  NOT NULL,
    message_key   VARCHAR(190) NOT NULL,
    locale        VARCHAR(10)  NOT NULL,
    message_text  TEXT NOT NULL,
    source        ENUM('core','custom') NOT NULL DEFAULT 'core',
    is_customized TINYINT(1) NOT NULL DEFAULT 0,
    default_hash  CHAR(40) NULL,
    is_outdated   TINYINT(1) NOT NULL DEFAULT 0,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by    BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_translation_message (domain, locale, message_key),
    KEY idx_translation_message_review (is_outdated, domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tax_class (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(50)  NOT NULL,
    name        VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tax_class_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tax_rate (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tax_class_id BIGINT UNSIGNED NOT NULL,
    country_code CHAR(2)      NOT NULL,
    rate_bp      INT UNSIGNED NOT NULL,
    valid_from   DATE NOT NULL,
    valid_to     DATE NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tax_rate (tax_class_id, country_code, valid_from),
    CONSTRAINT fk_tax_rate_class FOREIGN KEY (tax_class_id)
        REFERENCES tax_class (id) ON DELETE RESTRICT,
    CONSTRAINT ck_tax_rate_dates CHECK (valid_to IS NULL OR valid_to >= valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(100) NOT NULL,
    status       ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
    tax_class_id BIGINT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_code (code),
    KEY idx_product_status (status),
    KEY idx_product_tax_class (tax_class_id),
    CONSTRAINT fk_product_tax_class FOREIGN KEY (tax_class_id) REFERENCES tax_class (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_translation (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id       BIGINT UNSIGNED NOT NULL,
    locale           VARCHAR(10)  NOT NULL,
    name             VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) NOT NULL,
    short_description TEXT NULL,
    description      MEDIUMTEXT NULL,
    meta_title       VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_translation (product_id, locale),
    UNIQUE KEY uq_product_translation_slug (locale, slug),
    CONSTRAINT fk_product_translation_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE category (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id  BIGINT UNSIGNED NULL,
    code       VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_category_code (code),
    KEY idx_category_parent (parent_id, sort_order),
    CONSTRAINT fk_category_parent FOREIGN KEY (parent_id)
        REFERENCES category (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE category_translation (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    locale      VARCHAR(10)  NOT NULL,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_category_translation (category_id, locale),
    UNIQUE KEY uq_category_translation_slug (locale, slug),
    CONSTRAINT fk_category_translation_category FOREIGN KEY (category_id)
        REFERENCES category (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_category (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id  BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_category (product_id, category_id),
    KEY idx_product_category_category (category_id, sort_order),
    CONSTRAINT fk_product_category_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_category_category FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE attribute (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(100) NOT NULL,
    type          ENUM('text','number','enum','bool') NOT NULL,
    unit_id       BIGINT UNSIGNED NULL,
    is_filterable TINYINT(1) NOT NULL DEFAULT 0,
    sort_order    INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attribute_code (code),
    KEY idx_attribute_unit (unit_id),
    CONSTRAINT fk_attribute_unit FOREIGN KEY (unit_id) REFERENCES unit (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE attribute_translation (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attribute_id BIGINT UNSIGNED NOT NULL,
    locale       VARCHAR(10)  NOT NULL,
    name         VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attribute_translation (attribute_id, locale),
    CONSTRAINT fk_attribute_translation_attribute FOREIGN KEY (attribute_id)
        REFERENCES attribute (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE attribute_value (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attribute_id BIGINT UNSIGNED NOT NULL,
    code         VARCHAR(100) NOT NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    meta         JSON NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attribute_value (attribute_id, code),
    UNIQUE KEY uq_attribute_value_composite (id, attribute_id),
    CONSTRAINT fk_attribute_value_attribute FOREIGN KEY (attribute_id)
        REFERENCES attribute (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE attribute_value_translation (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attribute_value_id BIGINT UNSIGNED NOT NULL,
    locale             VARCHAR(10)  NOT NULL,
    label              VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attribute_value_translation (attribute_value_id, locale),
    CONSTRAINT fk_attribute_value_translation_value FOREIGN KEY (attribute_value_id)
        REFERENCES attribute_value (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_variant_axis (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id   BIGINT UNSIGNED NOT NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_variant_axis (product_id, attribute_id),
    CONSTRAINT fk_product_variant_axis_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_variant_axis_attribute FOREIGN KEY (attribute_id)
        REFERENCES attribute (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_variant (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id       BIGINT UNSIGNED NOT NULL,
    sku              VARCHAR(64) NOT NULL,
    combination_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    is_default       TINYINT(1) NOT NULL DEFAULT 0,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    weight_g         INT UNSIGNED NULL,
    length_mm        INT UNSIGNED NULL,
    width_mm         INT UNSIGNED NULL,
    height_mm        INT UNSIGNED NULL,
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    default_flag     TINYINT(1) GENERATED ALWAYS AS (IF(is_default = 1, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_variant_sku (sku),
    UNIQUE KEY uq_product_variant_combination (product_id, combination_hash),
    UNIQUE KEY uq_product_variant_single_default (product_id, default_flag),
    UNIQUE KEY uq_product_variant_composite (id, product_id),
    CONSTRAINT fk_product_variant_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_attribute_value (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id         BIGINT UNSIGNED NOT NULL,
    attribute_id       BIGINT UNSIGNED NOT NULL,
    attribute_value_id BIGINT UNSIGNED NULL,
    value_number       DECIMAL(20,6)   NULL,
    value_text         VARCHAR(255)    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_attribute_value (product_id, attribute_id),
    KEY idx_product_attribute_value_filter_number (attribute_id, value_number),
    KEY idx_product_attribute_value_filter_enum (attribute_id, attribute_value_id),
    CONSTRAINT fk_product_attribute_value_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_attribute_value_attribute FOREIGN KEY (attribute_id)
        REFERENCES attribute (id) ON DELETE RESTRICT,
    CONSTRAINT fk_product_attribute_value_composite FOREIGN KEY (attribute_value_id, attribute_id)
        REFERENCES attribute_value (id, attribute_id) ON DELETE RESTRICT,
    CONSTRAINT ck_product_attribute_value_exactly_one CHECK (
        (attribute_value_id IS NOT NULL) + (value_number IS NOT NULL) + (value_text IS NOT NULL) = 1
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_variant_attribute_value (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    product_id         BIGINT UNSIGNED NOT NULL,
    attribute_id       BIGINT UNSIGNED NOT NULL,
    attribute_value_id BIGINT UNSIGNED NULL,
    value_number       DECIMAL(20,6)   NULL,
    value_text         VARCHAR(255)    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_variant_attribute_value (product_variant_id, attribute_id),
    KEY idx_product_variant_attribute_value_filter_number (attribute_id, value_number),
    KEY idx_product_variant_attribute_value_filter_enum (attribute_id, attribute_value_id),
    CONSTRAINT fk_product_variant_attribute_value_product_variant FOREIGN KEY (product_variant_id, product_id)
        REFERENCES product_variant (id, product_id) ON DELETE CASCADE,
    CONSTRAINT fk_product_variant_attribute_value_axis FOREIGN KEY (product_id, attribute_id)
        REFERENCES product_variant_axis (product_id, attribute_id) ON DELETE CASCADE,
    CONSTRAINT fk_product_variant_attribute_value_attribute FOREIGN KEY (attribute_id)
        REFERENCES attribute (id) ON DELETE RESTRICT,
    CONSTRAINT fk_product_variant_attribute_value_composite FOREIGN KEY (attribute_value_id, attribute_id)
        REFERENCES attribute_value (id, attribute_id) ON DELETE CASCADE,
    CONSTRAINT ck_product_variant_attribute_value_exactly_one CHECK (
        (attribute_value_id IS NOT NULL) + (value_number IS NOT NULL) + (value_text IS NOT NULL) = 1
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_option (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id  BIGINT UNSIGNED NOT NULL,
    code        VARCHAR(100) NOT NULL,
    type        ENUM('choice','text') NOT NULL DEFAULT 'choice',
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_option (product_id, code),
    UNIQUE KEY uq_product_option_composite (id, product_id),
    CONSTRAINT fk_product_option_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_option_translation (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_option_id BIGINT UNSIGNED NOT NULL,
    locale            VARCHAR(10)  NOT NULL,
    name              VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_option_translation (product_option_id, locale),
    CONSTRAINT fk_product_option_translation_option FOREIGN KEY (product_option_id)
        REFERENCES product_option (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_option_value (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_option_id BIGINT UNSIGNED NOT NULL,
    code              VARCHAR(100) NOT NULL,
    sort_order        INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_option_value (product_option_id, code),
    UNIQUE KEY uq_product_option_value_composite (id, product_option_id),
    CONSTRAINT fk_product_option_value_option FOREIGN KEY (product_option_id)
        REFERENCES product_option (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_option_value_translation (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_option_value_id BIGINT UNSIGNED NOT NULL,
    locale                  VARCHAR(10)  NOT NULL,
    label                   VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_option_value_translation (product_option_value_id, locale),
    CONSTRAINT fk_product_option_value_translation_value FOREIGN KEY (product_option_value_id)
        REFERENCES product_option_value (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_relation (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id         BIGINT UNSIGNED NOT NULL,
    related_product_id BIGINT UNSIGNED NOT NULL,
    type               ENUM('accessory','alternative','compatible') NOT NULL,
    sort_order         INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_relation (product_id, related_product_id, type),
    CONSTRAINT fk_product_relation_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_relation_related FOREIGN KEY (related_product_id)
        REFERENCES product (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_image (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id         BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    file_path          VARCHAR(500) NOT NULL,
    alt_text           VARCHAR(255) NULL,
    sort_order         INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_product_image_product (product_id, sort_order),
    CONSTRAINT fk_product_image_product FOREIGN KEY (product_id)
        REFERENCES product (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_image_variant_composite FOREIGN KEY (product_variant_id, product_id)
        REFERENCES product_variant (id, product_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE variant_price (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    currency           CHAR(3) NOT NULL,
    amount_minor       BIGINT NOT NULL,
    tax_included       TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variant_price (product_variant_id, currency),
    CONSTRAINT fk_variant_price_variant FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE RESTRICT,
    CONSTRAINT fk_variant_price_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT,
    CONSTRAINT ck_variant_price_nonneg CHECK (amount_minor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Historie cen; aplikace zapisuje každou změnu skutečně nabízené spotřebitelské ceny.
-- Tato tabulka je auditní základ, nikoli automatická záruka souladu s pravidlem 30 dnů.
CREATE TABLE variant_price_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL,
    amount_minor BIGINT NOT NULL,
    tax_included TINYINT(1) NOT NULL DEFAULT 1,
    effective_at DATETIME(6) NOT NULL,
    change_source ENUM('initial','manual','promotion','import','system') NOT NULL DEFAULT 'manual',
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_price_history_point (product_variant_id, currency, effective_at),
    CONSTRAINT fk_variant_price_history_variant FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE RESTRICT,
    CONSTRAINT fk_variant_price_history_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT,
    CONSTRAINT ck_variant_price_history_amount CHECK (amount_minor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE option_value_price (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_option_value_id BIGINT UNSIGNED NOT NULL,
    currency                CHAR(3) NOT NULL,
    delta_minor             BIGINT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_option_value_price (product_option_value_id, currency),
    CONSTRAINT fk_option_value_price_value FOREIGN KEY (product_option_value_id) REFERENCES product_option_value (id) ON DELETE CASCADE,
    CONSTRAINT fk_option_value_price_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE stock_item (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    track_stock        TINYINT(1) NOT NULL DEFAULT 1,
    allow_backorder    TINYINT(1) NOT NULL DEFAULT 0,
    quantity_on_hand   DECIMAL(12,3) NOT NULL DEFAULT 0,
    quantity_reserved  DECIMAL(12,3) NOT NULL DEFAULT 0,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stock_item_variant (product_variant_id),
    CONSTRAINT fk_stock_item_variant FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE RESTRICT,
    CONSTRAINT ck_stock_item_reserved CHECK (quantity_reserved >= 0),
    CONSTRAINT ck_stock_item_integrity CHECK (
        allow_backorder = 1 OR (quantity_on_hand >= 0 AND quantity_on_hand >= quantity_reserved)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE stock_movement (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    delta              DECIMAL(12,3) NOT NULL,
    reason             ENUM('receipt','reservation','release','sale','return','adjustment') NOT NULL,
    reference_type     VARCHAR(32) NULL,
    reference_id       BIGINT UNSIGNED NULL,
    note               VARCHAR(255) NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_movement_variant (product_variant_id, created_at),
    KEY idx_stock_movement_reference (reference_type, reference_id),
    CONSTRAINT fk_stock_movement_variant FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE customer (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id     CHAR(26) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    first_name    VARCHAR(100) NULL,
    last_name     VARCHAR(100) NULL,
    phone         VARCHAR(32)  NULL,
    locale        VARCHAR(10)  NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_public_id (public_id),
    UNIQUE KEY uq_customer_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE customer_address (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    type        ENUM('billing','shipping') NOT NULL,
    name        VARCHAR(200) NOT NULL,
    company     VARCHAR(200) NULL,
    street      VARCHAR(200) NOT NULL,
    city        VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20)  NOT NULL,
    country_code CHAR(2)     NOT NULL,
    company_id  VARCHAR(20)  NULL,
    vat_id      VARCHAR(20)  NULL,
    is_default  TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_customer_address_customer (customer_id, type),
    CONSTRAINT fk_customer_address_customer FOREIGN KEY (customer_id)
        REFERENCES customer (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shipping_method (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(50)  NOT NULL,
    tax_class_id BIGINT UNSIGNED NOT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_method_code (code),
    CONSTRAINT fk_shipping_method_tax_class FOREIGN KEY (tax_class_id) REFERENCES tax_class (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shipping_method_translation (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipping_method_id BIGINT UNSIGNED NOT NULL,
    locale             VARCHAR(10)  NOT NULL,
    name               VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_method_translation (shipping_method_id, locale),
    CONSTRAINT fk_shipping_method_translation_shipping_method FOREIGN KEY (shipping_method_id)
        REFERENCES shipping_method (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shipping_method_price (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipping_method_id BIGINT UNSIGNED NOT NULL,
    currency           CHAR(3) NOT NULL,
    amount_minor       BIGINT NOT NULL,
    tax_included       TINYINT(1) NOT NULL DEFAULT 1,
    free_from_minor    BIGINT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipping_method_price (shipping_method_id, currency),
    CONSTRAINT fk_shipping_method_price_shipping_method FOREIGN KEY (shipping_method_id)
        REFERENCES shipping_method (id) ON DELETE CASCADE,
    CONSTRAINT fk_shipping_method_price_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT,
    CONSTRAINT ck_shipping_method_price_amount CHECK (amount_minor >= 0),
    CONSTRAINT ck_shipping_method_price_free_from CHECK (free_from_minor IS NULL OR free_from_minor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE payment_method (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(50)  NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_method_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE payment_method_translation (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    locale            VARCHAR(10)  NOT NULL,
    name              VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_method_translation (payment_method_id, locale),
    CONSTRAINT fk_payment_method_translation_payment_method FOREIGN KEY (payment_method_id)
        REFERENCES payment_method (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cart (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id   CHAR(26) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    currency    CHAR(3) NOT NULL,
    locale      VARCHAR(10) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cart_public_id (public_id),
    KEY idx_cart_customer (customer_id),
    KEY idx_cart_expires (expires_at),
    CONSTRAINT fk_cart_customer FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL,
    CONSTRAINT fk_cart_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT,
    CONSTRAINT fk_cart_language FOREIGN KEY (locale) REFERENCES language (code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cart_item (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id            BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    product_id         BIGINT UNSIGNED NOT NULL,
    quantity           DECIMAL(12,3) NOT NULL,
    configuration_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cart_item_cart (cart_id),
    UNIQUE KEY uq_cart_item_configuration (cart_id, configuration_hash),
    UNIQUE KEY uq_cart_item_id_product (id, product_id),
    CONSTRAINT fk_cart_item_cart FOREIGN KEY (cart_id)
        REFERENCES cart (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_item_variant_product FOREIGN KEY (product_variant_id, product_id)
        REFERENCES product_variant (id, product_id) ON DELETE RESTRICT,
    CONSTRAINT ck_cart_item_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cart_item_option (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_item_id            BIGINT UNSIGNED NOT NULL,
    product_id              BIGINT UNSIGNED NOT NULL,
    product_option_id       BIGINT UNSIGNED NOT NULL,
    product_option_value_id BIGINT UNSIGNED NULL,
    text_value              VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cart_item_option (cart_item_id, product_option_id),
    CONSTRAINT fk_cart_item_option_item FOREIGN KEY (cart_item_id, product_id)
        REFERENCES cart_item (id, product_id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_item_option_product_option FOREIGN KEY (product_option_id, product_id)
        REFERENCES product_option (id, product_id) ON DELETE RESTRICT,
    CONSTRAINT fk_cart_item_option_value FOREIGN KEY (product_option_value_id, product_option_id)
        REFERENCES product_option_value (id, product_option_id) ON DELETE RESTRICT,
    CONSTRAINT ck_cart_item_option_one CHECK (
        (product_option_value_id IS NOT NULL) + (text_value IS NOT NULL) = 1
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shop_order (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id             CHAR(26) NOT NULL,
    order_number          VARCHAR(20) NOT NULL,
    customer_id           BIGINT UNSIGNED NULL,
    email                 VARCHAR(190) NOT NULL,
    status                VARCHAR(32) NOT NULL,
    locale                VARCHAR(10) NOT NULL,
    currency              CHAR(3) NOT NULL,
    prices_include_tax    TINYINT(1) NOT NULL DEFAULT 1,
    subtotal_net_minor    BIGINT NOT NULL,
    tax_total_minor       BIGINT NOT NULL,
    grand_total_minor     BIGINT NOT NULL,
    billing_address       JSON NOT NULL,
    shipping_address      JSON NOT NULL,
    customer_note         VARCHAR(1000) NULL,
    placed_at             DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shop_order_public_id (public_id),
    UNIQUE KEY uq_shop_order_number (order_number),
    KEY idx_shop_order_customer (customer_id),
    KEY idx_shop_order_status (status, placed_at),
    CONSTRAINT fk_shop_order_customer FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL,
    CONSTRAINT fk_shop_order_currency FOREIGN KEY (currency) REFERENCES currency (code) ON DELETE RESTRICT,
    CONSTRAINT fk_shop_order_language FOREIGN KEY (locale) REFERENCES language (code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shop_order_item (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_order_id       BIGINT UNSIGNED NOT NULL,
    product_id          BIGINT UNSIGNED NULL,
    product_variant_id  BIGINT UNSIGNED NULL,
    sku                 VARCHAR(64)  NOT NULL,
    product_name        VARCHAR(255) NOT NULL,
    variant_label       VARCHAR(255) NULL,
    attributes_snapshot JSON NULL,
    quantity            DECIMAL(12,3) NOT NULL,
    base_unit_price_minor    BIGINT NOT NULL,
    options_unit_total_minor BIGINT NOT NULL DEFAULT 0,
    tax_rate_bp         INT UNSIGNED NOT NULL,
    line_net_minor      BIGINT NOT NULL,
    line_tax_minor      BIGINT NOT NULL,
    line_gross_minor    BIGINT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_shop_order_item_order (shop_order_id),
    KEY idx_shop_order_item_variant (product_variant_id),
    CONSTRAINT fk_shop_order_item_order FOREIGN KEY (shop_order_id)
        REFERENCES shop_order (id) ON DELETE CASCADE,
    CONSTRAINT ck_shop_order_item_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shop_order_item_option (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_order_item_id BIGINT UNSIGNED NOT NULL,
    option_name        VARCHAR(255) NOT NULL,
    value_label        VARCHAR(500) NOT NULL,
    price_delta_minor  BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_shop_order_item_option_item (shop_order_item_id),
    CONSTRAINT fk_shop_order_item_option_item FOREIGN KEY (shop_order_item_id)
        REFERENCES shop_order_item (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shop_order_adjustment (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_order_id BIGINT UNSIGNED NOT NULL,
    type          ENUM('shipping','payment_fee','discount','surcharge') NOT NULL,
    code          VARCHAR(50) NOT NULL,
    title         VARCHAR(255) NOT NULL,
    amount_net_minor BIGINT NOT NULL,
    amount_tax_minor BIGINT NOT NULL,
    amount_gross_minor BIGINT NOT NULL,
    tax_rate_bp   INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_shop_order_adjustment_order (shop_order_id),
    CONSTRAINT fk_shop_order_adjustment_order FOREIGN KEY (shop_order_id)
        REFERENCES shop_order (id) ON DELETE CASCADE,
    CONSTRAINT ck_shop_order_adjustment_amounts CHECK (amount_gross_minor = amount_net_minor + amount_tax_minor),
    CONSTRAINT ck_shop_order_adjustment_sign CHECK (
        (type = 'discount' AND amount_gross_minor <= 0)
        OR (type IN ('shipping','payment_fee','surcharge') AND amount_gross_minor >= 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shop_order_status_history (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_order_id BIGINT UNSIGNED NOT NULL,
    from_status   VARCHAR(32) NULL,
    to_status     VARCHAR(32) NOT NULL,
    note          VARCHAR(500) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_shop_order_status_history_order (shop_order_id, created_at),
    CONSTRAINT fk_shop_order_status_history_order FOREIGN KEY (shop_order_id)
        REFERENCES shop_order (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
