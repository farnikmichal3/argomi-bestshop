-- =============================================================================
-- ArgoMi BestShop · F3-27 · Order accounting invariants and immutable snapshots
-- MySQL 8.0.16+ · InnoDB
--
-- placed_at uzavírá účetní snapshot. Před uzavřením lze objednávku skládat;
-- při uzavření se ověří součty položek a úprav. Po uzavření nelze měnit
-- finanční souhrny, položky, jejich volby ani úpravy.
-- Historická inicializační migrace se zpětně neupravuje.
-- =============================================================================

ALTER TABLE shop_order
    ADD COLUMN adjustments_net_minor BIGINT NOT NULL DEFAULT 0
        AFTER subtotal_net_minor,
    ADD CONSTRAINT ck_shop_order_grand_total
        CHECK (
            grand_total_minor =
                subtotal_net_minor + adjustments_net_minor + tax_total_minor
        );

ALTER TABLE shop_order_item
    ADD CONSTRAINT ck_shop_order_item_line_gross
        CHECK (line_gross_minor = line_net_minor + line_tax_minor);

DELIMITER $$

CREATE TRIGGER trg_shop_order_bi_accounting
BEFORE INSERT ON shop_order
FOR EACH ROW
BEGIN
    IF NEW.placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An order must be assembled before it can be placed.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_bu_accounting
BEFORE UPDATE ON shop_order
FOR EACH ROW
BEGIN
    DECLARE v_items_net DECIMAL(65,0) DEFAULT 0;
    DECLARE v_items_tax DECIMAL(65,0) DEFAULT 0;
    DECLARE v_adjustments_net DECIMAL(65,0) DEFAULT 0;
    DECLARE v_adjustments_tax DECIMAL(65,0) DEFAULT 0;

    IF OLD.placed_at IS NOT NULL THEN
        IF NOT (NEW.placed_at <=> OLD.placed_at)
            OR NOT (NEW.subtotal_net_minor <=> OLD.subtotal_net_minor)
            OR NOT (NEW.adjustments_net_minor <=> OLD.adjustments_net_minor)
            OR NOT (NEW.tax_total_minor <=> OLD.tax_total_minor)
            OR NOT (NEW.grand_total_minor <=> OLD.grand_total_minor)
            OR NOT (NEW.currency <=> OLD.currency)
            OR NOT (NEW.prices_include_tax <=> OLD.prices_include_tax)
        THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'The accounting snapshot of a placed order is immutable.';
        END IF;
    ELSEIF NEW.placed_at IS NOT NULL THEN
        SELECT COALESCE(SUM(line_net_minor), 0),
               COALESCE(SUM(line_tax_minor), 0)
          INTO v_items_net, v_items_tax
          FROM shop_order_item
         WHERE shop_order_id = OLD.id;

        SELECT COALESCE(SUM(amount_net_minor), 0),
               COALESCE(SUM(amount_tax_minor), 0)
          INTO v_adjustments_net, v_adjustments_tax
          FROM shop_order_adjustment
         WHERE shop_order_id = OLD.id;

        IF NEW.subtotal_net_minor <> v_items_net THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Order subtotal does not match its item snapshots.';
        END IF;

        IF NEW.adjustments_net_minor <> v_adjustments_net THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Order adjustment total does not match its adjustments.';
        END IF;

        IF NEW.tax_total_minor <> v_items_tax + v_adjustments_tax THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Order tax total does not match its item and adjustment taxes.';
        END IF;

        IF NEW.grand_total_minor <>
            NEW.subtotal_net_minor + NEW.adjustments_net_minor + NEW.tax_total_minor
        THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Order grand total is inconsistent.';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_shop_order_bd_accounting
BEFORE DELETE ON shop_order
FOR EACH ROW
BEGIN
    IF OLD.placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A placed order cannot be deleted.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_bi_snapshot
BEFORE INSERT ON shop_order_item
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = NEW.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Items of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_bu_snapshot
BEFORE UPDATE ON shop_order_item
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    IF NEW.shop_order_id <> OLD.shop_order_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An order item cannot be moved to another order.';
    END IF;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = OLD.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Items of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_bd_snapshot
BEFORE DELETE ON shop_order_item
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = OLD.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Items of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_adjustment_bi_snapshot
BEFORE INSERT ON shop_order_adjustment
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = NEW.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Adjustments of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_adjustment_bu_snapshot
BEFORE UPDATE ON shop_order_adjustment
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    IF NEW.shop_order_id <> OLD.shop_order_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An adjustment cannot be moved to another order.';
    END IF;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = OLD.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Adjustments of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_adjustment_bd_snapshot
BEFORE DELETE ON shop_order_adjustment
FOR EACH ROW
BEGIN
    DECLARE v_placed_at DATETIME;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = OLD.shop_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Adjustments of a placed order are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_option_bi_snapshot
BEFORE INSERT ON shop_order_item_option
FOR EACH ROW
BEGIN
    DECLARE v_order_id BIGINT UNSIGNED;
    DECLARE v_placed_at DATETIME;

    SELECT shop_order_id
      INTO v_order_id
      FROM shop_order_item
     WHERE id = NEW.shop_order_item_id;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = v_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Options of a placed order item are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_option_bu_snapshot
BEFORE UPDATE ON shop_order_item_option
FOR EACH ROW
BEGIN
    DECLARE v_order_id BIGINT UNSIGNED;
    DECLARE v_placed_at DATETIME;

    IF NEW.shop_order_item_id <> OLD.shop_order_item_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'An item option cannot be moved to another item.';
    END IF;

    SELECT shop_order_id
      INTO v_order_id
      FROM shop_order_item
     WHERE id = OLD.shop_order_item_id;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = v_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Options of a placed order item are immutable.';
    END IF;
END$$

CREATE TRIGGER trg_shop_order_item_option_bd_snapshot
BEFORE DELETE ON shop_order_item_option
FOR EACH ROW
BEGIN
    DECLARE v_order_id BIGINT UNSIGNED;
    DECLARE v_placed_at DATETIME;

    SELECT shop_order_id
      INTO v_order_id
      FROM shop_order_item
     WHERE id = OLD.shop_order_item_id;

    SELECT placed_at
      INTO v_placed_at
      FROM shop_order
     WHERE id = v_order_id
     FOR UPDATE;

    IF v_placed_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Options of a placed order item are immutable.';
    END IF;
END$$

DELIMITER ;
