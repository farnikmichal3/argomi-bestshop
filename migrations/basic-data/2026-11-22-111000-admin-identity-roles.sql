-- ArgoMi BestShop · F8 · Initial system role names
-- Idempotent seed. Role names exist for the basic Identity integration only;
-- detailed ACL permissions and role-policy administration are deferred.

INSERT INTO admin_role (code, name, is_system)
VALUES
    ('admin', 'Administrator', 1),
    ('catalog_viewer', 'Catalog viewer', 1),
    ('catalog_manager', 'Catalog manager', 1),
    ('translation_manager', 'Translation manager', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_system = VALUES(is_system);
