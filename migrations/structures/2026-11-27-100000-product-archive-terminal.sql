-- =============================================================================
-- ArgoMi BestShop · F5-25 · Make product archival irreversible
-- MySQL 8.0.16+ · InnoDB
--
-- Archivace je terminální stav životního cyklu. Návrat do konceptu ani
-- opětovná aktivace by porušily historickou identitu produktu.
-- =============================================================================

DELIMITER $$

CREATE TRIGGER trg_product_bu_archive_terminal
BEFORE UPDATE ON product
FOR EACH ROW
BEGIN
    IF OLD.status = 'archived' AND NEW.status <> 'archived' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Archived products cannot leave the archived state.';
    END IF;
END$$

DELIMITER ;
