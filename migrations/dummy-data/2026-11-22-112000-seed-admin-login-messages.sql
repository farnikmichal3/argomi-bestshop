-- ArgoMi BestShop · F8-12 · Admin login/logout system messages
-- MySQL 8.0.16+ · InnoDB · utf8mb4
-- Idempotentní seed výchozích textů přihlášení a odhlášení administrátora.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('identity.login.title', 'cs', 'Přihlášení do administrace', 'core', 0, NULL, 0),
    ('identity.login.title', 'en', 'Administrator sign in', 'core', 0, NULL, 0),
    ('identity.login.email', 'cs', 'E-mail', 'core', 0, NULL, 0),
    ('identity.login.email', 'en', 'Email', 'core', 0, NULL, 0),
    ('identity.login.password', 'cs', 'Heslo', 'core', 0, NULL, 0),
    ('identity.login.password', 'en', 'Password', 'core', 0, NULL, 0),
    ('identity.login.submit', 'cs', 'Přihlásit se', 'core', 0, NULL, 0),
    ('identity.login.submit', 'en', 'Sign in', 'core', 0, NULL, 0),
    ('identity.login.required', 'cs', 'Vyplň e-mail a heslo.', 'core', 0, NULL, 0),
    ('identity.login.required', 'en', 'Enter your email and password.', 'core', 0, NULL, 0),
    ('identity.login.invalid', 'cs', 'Přihlášení se nezdařilo. Zkontroluj přihlašovací údaje.', 'core', 0, NULL, 0),
    ('identity.login.invalid', 'en', 'Sign in failed. Check your credentials.', 'core', 0, NULL, 0),
    ('identity.login.expired', 'cs', 'Formulář vypršel. Obnov stránku a zkus to znovu.', 'core', 0, NULL, 0),
    ('identity.login.expired', 'en', 'The form expired. Refresh the page and try again.', 'core', 0, NULL, 0),
    ('identity.logout.submit', 'cs', 'Odhlásit se', 'core', 0, NULL, 0),
    ('identity.logout.submit', 'en', 'Sign out', 'core', 0, NULL, 0),
    ('identity.logout.expired', 'cs', 'Požadavek na odhlášení vypršel. Obnov stránku a zkus to znovu.', 'core', 0, NULL, 0),
    ('identity.logout.expired', 'en', 'The sign-out request expired. Refresh the page and try again.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
