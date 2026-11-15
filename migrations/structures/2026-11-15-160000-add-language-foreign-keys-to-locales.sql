-- =============================================================================
-- ArgoMi BestShop · F3-26 · Enforce language references for every locale
-- MySQL 8.0.16+ · InnoDB
--
-- Každý locale musí odkazovat na existující jazyk. Povinné lokalizace chrání
-- jazyk pomocí RESTRICT; nepovinná preference zákazníka se při odebrání jazyka
-- nastaví na NULL. Historická inicializační migrace se neupravuje.
-- =============================================================================

ALTER TABLE unit_translation
    ADD KEY idx_unit_translation_locale (locale),
    ADD CONSTRAINT fk_unit_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE translation_message
    ADD KEY idx_translation_message_locale (locale),
    ADD CONSTRAINT fk_translation_message_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE product_translation
    ADD KEY idx_product_translation_locale (locale),
    ADD CONSTRAINT fk_product_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE category_translation
    ADD KEY idx_category_translation_locale (locale),
    ADD CONSTRAINT fk_category_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE attribute_translation
    ADD KEY idx_attribute_translation_locale (locale),
    ADD CONSTRAINT fk_attribute_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE attribute_value_translation
    ADD KEY idx_attribute_value_translation_locale (locale),
    ADD CONSTRAINT fk_attribute_value_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE product_option_translation
    ADD KEY idx_product_option_translation_locale (locale),
    ADD CONSTRAINT fk_product_option_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE product_option_value_translation
    ADD KEY idx_product_option_value_translation_locale (locale),
    ADD CONSTRAINT fk_product_option_value_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE shipping_method_translation
    ADD KEY idx_shipping_method_translation_locale (locale),
    ADD CONSTRAINT fk_shipping_method_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE payment_method_translation
    ADD KEY idx_payment_method_translation_locale (locale),
    ADD CONSTRAINT fk_payment_method_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT;

ALTER TABLE customer
    ADD KEY idx_customer_locale (locale),
    ADD CONSTRAINT fk_customer_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE SET NULL;
