-- =============================================================================
-- ArgoMi BestShop · F3-28 · Archive products and variants instead of deleting
-- MySQL 8.0.16+ · InnoDB
--
-- Katalogové produkty a varianty jsou dlouhodobé identity, na které mohou
-- odkazovat ceny, sklad, košíky, objednávky a další obchodní záznamy.
-- Fyzické mazání proto zakazujeme; životní cyklus se řídí archivací produktu
-- a deaktivací varianty. Historická inicializační migrace se neupravuje.
-- =============================================================================

ALTER TABLE product_variant
    DROP FOREIGN KEY fk_product_variant_product,
    ADD CONSTRAINT fk_product_variant_product_restrict
        FOREIGN KEY (product_id)
        REFERENCES product (id)
        ON DELETE RESTRICT;

DELIMITER $$

CREATE TRIGGER trg_product_bd_archive_only
BEFORE DELETE ON product
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Products must be archived instead of deleted.';
END$$

CREATE TRIGGER trg_product_variant_bd_archive_only
BEFORE DELETE ON product_variant
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Product variants must be deactivated instead of deleted.';
END$$

CREATE TRIGGER trg_product_au_deactivate_archived_variants
AFTER UPDATE ON product
FOR EACH ROW
BEGIN
    IF NEW.status = 'archived' AND OLD.status <> 'archived' THEN
        UPDATE product_variant
           SET is_active = 0
         WHERE product_id = NEW.id
           AND is_active = 1;
    END IF;
END$$

CREATE TRIGGER trg_product_variant_bi_archived
BEFORE INSERT ON product_variant
FOR EACH ROW
BEGIN
    DECLARE v_product_status VARCHAR(20);

    SELECT status
      INTO v_product_status
      FROM product
     WHERE id = NEW.product_id;

    IF v_product_status = 'archived' AND NEW.is_active = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An archived product cannot receive an active variant.';
    END IF;
END$$

CREATE TRIGGER trg_product_variant_bu_archived
BEFORE UPDATE ON product_variant
FOR EACH ROW
BEGIN
    DECLARE v_product_status VARCHAR(20);

    IF NEW.is_active = 1 THEN
        SELECT status
          INTO v_product_status
          FROM product
         WHERE id = NEW.product_id;

        IF v_product_status = 'archived' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'A variant of an archived product cannot be activated.';
        END IF;
    END IF;
END$$

DELIMITER ;
