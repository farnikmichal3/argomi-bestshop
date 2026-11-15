-- =============================================================================
-- ArgoMi BestShop · F3-32 · Demo order isolation
-- MySQL 8.0.16+ · InnoDB
--
-- Demo objednávky zůstávají plnohodnotnými historickými snapshoty, ale nesmějí
-- vstoupit do provozního Outboxu. Objednávkové události používají prefix order.
-- =============================================================================

ALTER TABLE shop_order
    ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD CONSTRAINT ck_shop_order_is_demo CHECK (is_demo IN (0, 1));

ALTER TABLE outbox_message
    ADD COLUMN shop_order_id BIGINT UNSIGNED NULL AFTER event_name,
    ADD KEY idx_outbox_shop_order (shop_order_id),
    ADD CONSTRAINT fk_outbox_shop_order
        FOREIGN KEY (shop_order_id) REFERENCES shop_order (id) ON DELETE RESTRICT;

DELIMITER $$

-- Příznak lze nastavit při vytvoření objednávky. Později je neměnný;
-- jediná povolená změna 0 → 1 je označení čtyř pevně určených demo objednávek.
CREATE TRIGGER trg_shop_order_bu_demo_immutable
BEFORE UPDATE ON shop_order
FOR EACH ROW
BEGIN
    IF NOT (NEW.is_demo <=> OLD.is_demo)
        AND NOT (
            OLD.is_demo = 0
            AND NEW.is_demo = 1
            AND NEW.order_number IN ('2026-000001', '2026-000002', '2026-000003', '2026-000004')
        )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The demo classification of an order is immutable.';
    END IF;

    IF OLD.is_demo = 1 AND NEW.is_demo <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A demo order cannot be converted into a live order.';
    END IF;
END$$

-- Objednávkové události musí nést explicitní FK. Demo objednávky nesmějí
-- být zařazeny do fronty, ze které worker spouští externí handlery.
CREATE TRIGGER trg_outbox_message_bi_demo_order
BEFORE INSERT ON outbox_message
FOR EACH ROW
BEGIN
    DECLARE v_is_demo TINYINT(1);

    IF NEW.event_name LIKE 'order.%' AND NEW.shop_order_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An order Outbox event must reference its shop order.';
    END IF;

    IF NEW.shop_order_id IS NOT NULL THEN
        SELECT is_demo
          INTO v_is_demo
          FROM shop_order
         WHERE id = NEW.shop_order_id;

        IF v_is_demo = 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Demo orders cannot emit operational Outbox events.';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_outbox_message_bu_demo_order
BEFORE UPDATE ON outbox_message
FOR EACH ROW
BEGIN
    DECLARE v_is_demo TINYINT(1);

    IF NEW.event_name LIKE 'order.%' AND NEW.shop_order_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An order Outbox event must reference its shop order.';
    END IF;

    IF NEW.shop_order_id IS NOT NULL THEN
        SELECT is_demo
          INTO v_is_demo
          FROM shop_order
         WHERE id = NEW.shop_order_id;

        IF v_is_demo = 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Demo orders cannot emit operational Outbox events.';
        END IF;
    END IF;
END$$

DELIMITER ;
