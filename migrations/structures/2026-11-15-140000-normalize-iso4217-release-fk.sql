-- =============================================================================
-- ArgoMi BestShop · Database convention · ISO 4217 release foreign key
-- MySQL 8.0.16+ · InnoDB
--
-- Sjednocení názvu zdrojového sloupce FK s konvencí
-- <referenced_table>_id. Původní migrace se zpětně neupravuje.
-- =============================================================================

ALTER TABLE iso4217_currency
    RENAME COLUMN release_id TO iso4217_catalog_release_id;
