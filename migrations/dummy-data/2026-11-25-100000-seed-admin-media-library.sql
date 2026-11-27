-- ArgoMi BestShop · F5-22 · Admin media library messages
-- MySQL 8.0.16+ · InnoDB · utf8mb4
-- Idempotentní systémové texty knihovny médií.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('admin.navigation.media', 'cs', 'Média', 'core', 0, NULL, 0),
    ('admin.navigation.media', 'en', 'Media', 'core', 0, NULL, 0),
    ('admin.navigation.media_library', 'cs', 'Knihovna médií', 'core', 0, NULL, 0),
    ('admin.navigation.media_library', 'en', 'Media library', 'core', 0, NULL, 0),
    ('media.title', 'cs', 'Knihovna médií', 'core', 0, NULL, 0),
    ('media.title', 'en', 'Media library', 'core', 0, NULL, 0),
    ('media.description', 'cs', 'Originály zůstávají zachované, náhledy se vytvářejí automaticky.', 'core', 0, NULL, 0),
    ('media.description', 'en', 'Original files are preserved and previews are generated automatically.', 'core', 0, NULL, 0),
    ('media.upload.title', 'cs', 'Nahrát obrázek', 'core', 0, NULL, 0),
    ('media.upload.title', 'en', 'Upload image', 'core', 0, NULL, 0),
    ('media.upload.file', 'cs', 'Obrázek', 'core', 0, NULL, 0),
    ('media.upload.file', 'en', 'Image', 'core', 0, NULL, 0),
    ('media.upload.submit', 'cs', 'Nahrát do knihovny', 'core', 0, NULL, 0),
    ('media.upload.submit', 'en', 'Upload to library', 'core', 0, NULL, 0),
    ('media.upload.error.required', 'cs', 'Vyber obrázek k nahrání.', 'core', 0, NULL, 0),
    ('media.upload.error.required', 'en', 'Select an image to upload.', 'core', 0, NULL, 0),
    ('media.upload.error.invalid', 'cs', 'Soubor není platný podporovaný obrázek nebo překračuje povolené limity.', 'core', 0, NULL, 0),
    ('media.upload.error.invalid', 'en', 'The file is not a supported image or exceeds the allowed limits.', 'core', 0, NULL, 0),
    ('media.upload.success', 'cs', 'Obrázek byl nahrán do knihovny médií.', 'core', 0, NULL, 0),
    ('media.upload.success', 'en', 'The image was uploaded to the media library.', 'core', 0, NULL, 0),
    ('media.read_only', 'cs', 'Máš pouze oprávnění k prohlížení knihovny.', 'core', 0, NULL, 0),
    ('media.read_only', 'en', 'You have read-only access to the library.', 'core', 0, NULL, 0),
    ('media.library.title', 'cs', 'Nahrané soubory', 'core', 0, NULL, 0),
    ('media.library.title', 'en', 'Uploaded files', 'core', 0, NULL, 0),
    ('media.empty', 'cs', 'Knihovna zatím neobsahuje žádné obrázky.', 'core', 0, NULL, 0),
    ('media.empty', 'en', 'The library does not contain any images yet.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
