-- ArgoMi BestShop · F5-22 · Media publication and picker messages
-- MySQL 8.0.16+ · InnoDB · utf8mb4
-- Samostatný seed kvůli databázím, které již aplikovaly původní seed knihovny médií.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('media.publication.title', 'cs', 'Veřejné publikování', 'core', 0, NULL, 0),
    ('media.publication.title', 'en', 'Public publication', 'core', 0, NULL, 0),
    ('media.publication.description', 'cs', 'Pouze publikované obrázky lze vložit do veřejného obsahu. Odpublikování okamžitě zablokuje veřejný přístup.', 'core', 0, NULL, 0),
    ('media.publication.description', 'en', 'Only published images can be inserted into public content. Unpublishing immediately blocks public access.', 'core', 0, NULL, 0),
    ('media.publication.asset', 'cs', 'Obrázek', 'core', 0, NULL, 0),
    ('media.publication.asset', 'en', 'Image', 'core', 0, NULL, 0),
    ('media.publication.publish_checkbox', 'cs', 'Publikovat (zaškrtnuto) / odpublikovat (nezaškrtnuto)', 'core', 0, NULL, 0),
    ('media.publication.publish_checkbox', 'en', 'Publish (checked) / unpublish (unchecked)', 'core', 0, NULL, 0),
    ('media.publication.submit', 'cs', 'Uložit stav publikování', 'core', 0, NULL, 0),
    ('media.publication.submit', 'en', 'Save publication state', 'core', 0, NULL, 0),
    ('media.publication.published', 'cs', 'Veřejné', 'core', 0, NULL, 0),
    ('media.publication.published', 'en', 'Public', 'core', 0, NULL, 0),
    ('media.publication.private', 'cs', 'Soukromé', 'core', 0, NULL, 0),
    ('media.publication.private', 'en', 'Private', 'core', 0, NULL, 0),
    ('media.publication.success', 'cs', 'Obrázek byl publikován.', 'core', 0, NULL, 0),
    ('media.publication.success', 'en', 'The image was published.', 'core', 0, NULL, 0),
    ('media.publication.unpublished', 'cs', 'Veřejný přístup k obrázku byl odebrán.', 'core', 0, NULL, 0),
    ('media.publication.unpublished', 'en', 'Public access to the image was revoked.', 'core', 0, NULL, 0),
    ('media.publication.error.required', 'cs', 'Vyber obrázek.', 'core', 0, NULL, 0),
    ('media.publication.error.required', 'en', 'Select an image.', 'core', 0, NULL, 0),
    ('media.publication.error.invalid', 'cs', 'Požadavek na změnu publikování není platný.', 'core', 0, NULL, 0),
    ('media.publication.error.invalid', 'en', 'The publication request is invalid.', 'core', 0, NULL, 0),
    ('media.publication.error.protection', 'cs', 'Platnost formuláře vypršela. Obnov stránku a zkus to znovu.', 'core', 0, NULL, 0),
    ('media.publication.error.protection', 'en', 'The form has expired. Refresh the page and try again.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
