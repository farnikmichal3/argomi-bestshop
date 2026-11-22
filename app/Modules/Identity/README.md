# Identity – administrátorská identita

Modul Identity vlastní **administrátorské účty** a jejich autentizaci. Je oddělen od modulu Customer, který bude spravovat zákaznické účty. Aktuální implementace poskytuje základní přihlášení administrátora a serverovou ochranu existujících admin presenterů; nejde o kompletní RBAC/ACL.

## Aktuálně implementováno

- Databázové tabulky:
  - `admin_user`: e-mail, password hash, stav `active/disabled`, timestamps a `last_login_at`.
  - `admin_role`: stabilní kód role a systémový příznak.
  - `admin_user_role`: vazba účtu a rolí.
- SQL migrace schématu a idempotentní seed základních rolí.
- `AdminAccountCredentials`: read-only DTO; password hash se nikdy nevkládá do Nette identity.
- `AdminAuthenticationRepository`: načtení účtu/rolí, záznam posledního přihlášení a transakční vytvoření administrátora s rolí `admin`.
- `AdminAuthenticator`: ověřuje hash hesla, odmítá disabled účty, vrací generickou chybu a provádí dummy password verification při neznámém/neaktivním účtu.
- CLI příkaz `php bin/console identity:admin:create` vytvoří účet. Heslo se zadává skrytě a musí mít nejméně 14 znaků. Žádný administrátor ani heslo se neseeduje migrací.
- Přihlášení na `/admin/login`; po přihlášení se přesměruje na `/admin`.
- Aktuální admin presentery Dashboard, Products, Translations a Catalog/Categories používají společnou serverovou autentizační ochranu `AdminAccessTrait`.
- Odhlášení je formulářem POST s Nette Forms CSRF ochranou. Zruší pouze administrátorskou identitu a administrátorskou session.
- Přihlášení používá Nette `SessionStorage`, který při uložení identity regeneruje session ID. Přihlášení má posuvnou dobu nečinnosti 30 minut.
- Texty login/logout jsou seedovány do databázových překladů v češtině a angličtině.

## Oddělení session a cookie

Výběr session probíhá ve `www/index.php` ještě před inicializací Bootstrapu, Tracy a Nette DI. `SessionCookieConfigurator` podle cesty požadavku vybere PHP session name a předá request-specific konfiguraci do `Bootstrap`. Bootstrap ji přidá po lokální konfiguraci a ještě před vytvořením DI kontejneru, tedy před Nette smart session autostartem.

| Část | Session cookie | Path | SameSite |
|---|---|---|---|
| Administrace | `ARGOMI_ADMIN_SESSID` | `/admin` (včetně případného base path) | `Strict` |
| Veřejná část | `ARGOMI_SESSID` | aplikační base path | `Lax` |

Obě cookies mají `HttpOnly`; atribut `Secure` se zapíná podle HTTPS požadavku. Serverová data se navíc ukládají do fyzicky oddělených adresářů `var/sessions/admin` a `var/sessions/public`; ani shodné session ID proto nesdílí stejné úložiště. Nette dostává odpovídající `http.cookiePath`, `http.cookieSecure`, `session.cookieSamesite` a `session.savePath` konfiguraci už při sestavení kontejneru. Test ověřuje rozdílné názvy, cesty, save paths a nezávislé načítání session dat.

## Základní role a oprávnění

| Kód | Účel |
|---|---|
| `admin` | základní administrátor; v aktuálních Catalog a Translation resolvers má správu těchto modulů |
| `catalog_viewer` | čtení katalogu |
| `catalog_manager` | správa katalogu |
| `translation_manager` | správa překladů |

Role `admin` je nyní minimální provozní role, nikoliv obecná ACL matice. Moduly nadále kontrolují svá oprávnění přes vlastní permission/resolver kontrakty.

## Záměrně mimo aktuální rozsah

- Kompletní ACL/RBAC matice a jemnozrnné oprávnění.
- Správa administrátorských účtů a rolí přes UI.
- Dvoufaktorové ověření, throttling/lockout a auditní log.
- Obnova hesla, pozvánky a další lifecycle účtu.
- Pokročilá politika session a bezpečnostní monitoring.

Tyto části zůstávají v navazujících krocích F8. Základní přihlášení nesmí být zaměňováno za dokončený bezpečnostní program.

## Ověření

- Integrační testy ověřují schéma, autentizaci, vytvoření administrátora a přiřazení role.
- Test session konfigurace ověřuje oddělené názvy, cookie parametry a nezávislost uložených session dat.
- CI a lokální `check.bat` se považují za úspěšné pouze po potvrzení konkrétního běhu.
