-- =============================================================================
-- ArgoMi BestShop · F3-24 · Protect variant attribute values from implicit deletion
-- MySQL 8.0.16+ · InnoDB
--
-- Hodnoty varianty jsou obchodní data. Smazání enum hodnoty nebo osy variant
-- proto musí být odmítnuto, dokud existuje přiřazení k variantě.
-- Překlady enum hodnot zůstávají navázány přes CASCADE: nejsou daty varianty.
-- Historická inicializační migrace se zpětně neupravuje.
--
-- Nové názvy FK jsou záměrně odlišné od původních. MySQL může při nahrazení
-- constraintu v jednom ALTER TABLE vyhodnotit původní název jako stále obsazený.
-- =============================================================================

ALTER TABLE product_variant_attribute_value
    DROP FOREIGN KEY fk_product_variant_attribute_value_axis,
    ADD CONSTRAINT fk_pvav_axis_restrict
        FOREIGN KEY (product_id, attribute_id)
        REFERENCES product_variant_axis (product_id, attribute_id)
        ON DELETE RESTRICT;

ALTER TABLE product_variant_attribute_value
    DROP FOREIGN KEY fk_product_variant_attribute_value_composite,
    ADD CONSTRAINT fk_pvav_value_restrict
        FOREIGN KEY (attribute_value_id, attribute_id)
        REFERENCES attribute_value (id, attribute_id)
        ON DELETE RESTRICT;
