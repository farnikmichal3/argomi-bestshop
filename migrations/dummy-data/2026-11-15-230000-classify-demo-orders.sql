-- =============================================================================
-- ArgoMi BestShop · F3-32 · Explicit demo order classification
-- Spouští se po strukturálních migracích a po založení demo objednávek.
-- =============================================================================

UPDATE shop_order
SET is_demo = 1
WHERE order_number IN ('2026-000001', '2026-000002', '2026-000003', '2026-000004')
  AND is_demo = 0;

DROP PROCEDURE IF EXISTS assert_demo_order_classification;

DELIMITER $$

CREATE PROCEDURE assert_demo_order_classification()
BEGIN
    IF (
        SELECT COUNT(*)
        FROM shop_order
        WHERE order_number IN ('2026-000003', '2026-000004')
          AND is_demo = 1
          AND placed_at IS NOT NULL
    ) <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'All four seeded demo orders must be classified as demo.';
    END IF;
END$$

CALL assert_demo_order_classification()$$

DELIMITER ;

DROP PROCEDURE assert_demo_order_classification;
