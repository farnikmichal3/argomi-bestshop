-- ArgoMi BestShop · F5-19 · Admin attribute navigation label (cs/en)
SET NAMES utf8mb4;
START TRANSACTION;
INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('admin.navigation.attributes', 'cs', 'Atributy', 'core', 0, NULL, 0),
    ('admin.navigation.attributes', 'en', 'Attributes', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;
COMMIT;
