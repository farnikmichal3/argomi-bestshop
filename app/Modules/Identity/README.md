# Identity – administrátorská identita

Modul Identity vlastní **administrátorské účty** a jejich autentizaci. Je oddělen od modulu Customer, který bude spravovat zákaznické účty. Identity zatím poskytuje pouze základ autentizace; nejde o kompletní RBAC/ACL ani hotové přihlášení do administrace.

## Aktuálně implementováno

- Databázové tabulky:
  - `admin_user`: e-mail, password hash, stav `active/disabled`, timestamps a `last_login_at`.
  - `admin_role`: stabilní kód role a systémový příznak.
  - `admin_user_role`: vazba účtu a rolí, unikátnost přiřazení a FK.
- SQL migrace: `migrations/structures/2026-11-22-110000-admin-identity-foundation.sql`.
- Idempotentní seed základních rolí: `migrations/basic-data/2026-11-22-111000-admin-identity-roles.sql`.
- `AdminAccountCredentials`: read-only DTO pro credentials a role. Password hash se nikdy nevkládá do Nette identity.
- `AdminAuthenticationRepository`: načtení účtu podle normalizovaného e-mailu, načtení rolí a aktualizace posledního přihlášení.
- `AdminAuthenticator`: implementace Nette `Authenticator`; ověřuje hash hesla, odmítá disabled účty, vrací generickou chybu a provádí dummy password verification při neznámém/neaktivním účtu.
- Explicitní registrace authenticatoru v `config/services.neon`.
- Integrační testy schématu a autentizace: `tests/Modules/Identity/AdminIdentitySchemaIntegration.phpt` a `AdminAuthenticatorIntegration.phpt`.

## Seedované role

| Kód | Účel / aktuální rozsah |
|---|---|
| `admin` | základní role administrátora; úplný ACL význam zatím není definován |
| `catalog_viewer` | čtení katalogu podle aktuálního Catalog resolveru |
| `catalog_manager` | správa katalogu podle aktuálního Catalog resolveru |
| `translation_manager` | správa překladů podle aktuální Translation resolveru |

Role `translation_viewer` zatím není součástí základního role seedu. Nesmí se předpokláávat, že ji přihlášený uživatel automaticky získá.

## Bezpečnostní hranice

- Účty disabled nelze autentizovat.
- Chybová zpráva neodhaluje, zda účet existuje nebo je deaktivovaný.
- Do Nette identity se předává pouze ID, role a e-mail jako doplňující data, nikoli heslo/hash.
- Neexistuje žádný seedovaný výchozí admin účet ani známé heslo.
- Role se vyhodnocují přes moduly permission/resolver; samotné přihlášení nepřidává oprávnění ke Catalog nebo Translation.
- Tato základní implementace neřeší kompletní ACL matici, dvoufaktorové ověření, throttling/lockout, auditní log ani reset hesla.

## Dosud neimplementováno (navazující F8-12 a další)

- Admin login/logout UI, presenter, formulář a šablona.
- Ochrana administrátorských rout/presenterů a bezpečné přesměrování po přihlášení.
- Oddělená administrátorská session/cookie, regenerace session ID a časové limity.
- CSRF a POST-only logout.
- Bezpečné první zřízení admin účtu (bez seedovaného hesla).
- Omezení pokusů o přihlášení, audit, 2FA a správa účtů/rolí.
- Kompletní ACL/RBAC model.

## Ověření

Foundation prošla GitHub Actions CI a uživatel potvrdil lokální `check.bat` s výsledkem `All active checks passed.`. Toto potvrzení se vztahuje pouze na aktuálně implementovaný foundation, nikoli na výše uvedené budoucí části.
