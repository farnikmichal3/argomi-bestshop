-- =============================================================================
-- ArgoMi BestShop · F5-15 · Protect category deletion
-- MySQL 8.0.16+ · InnoDB
--
-- Kategorie s přiřazenými produkty nelze odstranit. Aplikační vrstva navíc
-- odmítá odstranění kategorií s potomky; tato FK ochrana brání tichému
-- odstranění přiřazení i při zápisu mimo aplikační službu nebo při souběhu.
-- =============================================================================

ALTER TABLE product_category
    DROP FOREIGN KEY fk_product_category_category,
    ADD CONSTRAINT fk_product_category_category
        FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT;
