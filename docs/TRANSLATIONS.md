# ArgoMi-BestShop – překladový subsystém

**Stav:** závazný architektonický standard; implementace postupně  
**Revize:** 1.25 · 27. 11. 2026

## 1. Základní pravidlo

**Databáze je jediným zdrojem pravdy pro veškerý překládaný text. Bez výjimek.**

V databázi musí být uloženo každé jazykové znění:
- systémových textů a hlášek dodávaných aplikací,
- obsahových údajů doménových entit,
- vlastních textů vytvořených administrátorem nebo obchodníkem,
- názvů, popisů, validačních zpráv, stavů a textů uživatelského rozhraní.

Překladové hodnoty nesmějí být součástí aplikačního kódu ani runtime souborových katalogů (PHP, Latte, JSON, NEON, YAML, XML, JavaScript, CSS/SCSS apod.). **Výjimkou jsou databázové migrace a seed skripty**, které smějí obsahovat výchozí systémové překladové hodnoty jako verzovaný instalační zdroj a vložit je do databáze. Tyto soubory nejsou runtime úložištěm: aplikace z nich nikdy překlady nenačítá. Po instalaci je jediným zdrojem pravdy databáze; žádný souborový katalog ani runtime fallback neexistuje. V aplikačním kódu smějí být pouze stabilní klíče/identifikátory a technická logika.

Toto pravidlo platí i pro systémové překlady, které administrátor nesmí měnit. Jejich ochrana se realizuje databázovým modelem, aplikační autorizací a databázovými omezeními, nikoliv uložením textu mimo databázi.

## 2. Vlastnictví a domény

Překlady zůstávají rozdělené podle vlastníka a domény; nesmí vzniknout jedna neomezeně rostoucí tabulka pro všechny texty.

### Obsahové překlady
Patří k modulu, který vlastní překládanou entitu:
- Catalog: produkt, kategorie, atribut, hodnota atributu, volby a další katalogový obsah,
- Shipping: názvy a popisy způsobů dopravy,
- Payments: názvy a popisy platebních metod,
- další moduly: vlastní obsahové texty podle jejich domény.

Příklady existujících tabulek: `product_translation`, `category_translation`, `attribute_translation`, `attribute_value_translation`, `product_option_translation`, `product_option_value_translation`, `shipping_method_translation`, `payment_method_translation`, `unit_translation`.

### Systémové texty
Systémové texty mají doménu a stabilní klíč. Jejich hodnoty pro jednotlivé jazyky jsou uloženy v databázi. Modul Translation poskytuje společnou infrastrukturu, ale doménové klíče zůstávají vlastnictvím příslušných modulů.

- `Catalog`, `Cart` a `Checkout` vlastní systémové texty příslušných obchodních modulů.
- `Admin` je samostatná vlastnická doména pro texty administrativního rozhraní (včetně správy překladů). Texty administrace se nesmějí ukládat do `Catalog` ani do jiné obchodní domény.
- Každá doména má vlastní databázovou tabulku. Pro `Admin` je to `admin_message_translation`; její struktura odpovídá ostatním systémovým překladovým tabulkám a zachovává ochranné atributy `source`, `is_customized`, `default_hash` a `is_outdated`.
- Veřejný `TranslationDomain::Admin` a interní `MessageDomain::Admin` mapují na toto úložiště. Fallback i cache zůstávají izolované podle domény.
- Výchozí texty Admin budou dodány verzovaným idempotentním SQL seedem; runtime načítání zůstává výhradně databázové.

## 3. Systémové hodnoty a ochrana

- Systémové klíče a jejich jazykové hodnoty jsou databázová data.
- Výchozí systémové hodnoty se do databáze dostávají řízenou databázovou migrací nebo seedem. SQL migrace a seed skripty smějí obsahovat jejich výchozí znění; jde o instalační/aktualizační zdroj, nikoliv o runtime úložiště.
- Systémové záznamy mají jednoznačně rozpoznatelný původ a stav ochrany.
- Administrátor je může zobrazit, vyhledat a zkontrolovat, ale nesmí změnit ani odstranit chráněný systémový text.
- Aktualizace aplikace může přinést nové nebo změněné systémové hodnoty; databázová aktualizace musí bezpečně rozpoznat novou verzi a nesmí přepsat vlastní text.
- Vlastní překlady jsou samostatně identifikované databázové záznamy s vlastníkem a oprávněními.
- Vlastní překlad musí být možné upravit, odstranit nebo vrátit na systémovou hodnotu.
- Ochrana se vynucuje v aplikační vrstvě i databázovými pravidly, kde to dává smysl. Pouhé skrytí tlačítka není ochrana.

## 4. Oprávnění

Administrátor může podle oprávnění:
- vytvořit vlastní text v povolené doméně,
- přidat překlad do aktivního jazyka,
- upravit vlastní obsahové překlady,
- spravovat překlady, které obchodník vlastní.

Administrátor nesmí:
- změnit chráněnou systémovou hodnotu ani systémový klíč,
- odstranit systémový záznam,
- měnit překlady mimo rozsah svého oprávnění.

Role a oprávnění spravuje Identity. Modul Translation vynucuje serverové role `translation_viewer` (pouze čtení) a `translation_manager` (čtení i správa). Oprávnění se vyhodnocuje pouze pro přihlášeného uživatele; skrytí formuláře není bezpečnostní hranice.

## 5. Jazyky, fallback a formátování

- Výchozí jazyk instalace je čeština (`cs`), další počáteční jazyk angličtina (`en`).
- Jazyk se určuje z databázové evidence jazyků a aktivního kontextu; jazykové hodnoty se nesmějí natvrdo zapisovat do aplikační logiky.
- Chybějící překlad se řeší databázovým fallbackem: požadované locale, výchozí aktivní locale z `LocaleProvider`, poté stabilní klíč. Fallback nikdy nepřechází mezi vlastnickými doménami; ICU formátování používá locale katalogu, ze kterého byl text skutečně nalezen. Nikdy se nevrací souborová hodnota ani prázdný text.
- Parametrizované zprávy používají ICU MessageFormat přes PHP `intl`; samotné vzory zpráv jsou uložené v databázi.
- `DatabaseTranslator` přijímá pojmenované argumenty jako jedno pole (`translate($key, ['count' => 2])`) nebo poziční argumenty samostatně (`translate($key, 'Košík', 2)`). Podporované hodnoty jsou `string`, `int`, `float`, `null` a `Stringable`; locale se vždy bere z katalogu.
- Bez parametrů se vzor vrací beze změny. Chybný ICU vzor vyvolá `MessageFormattingException`; chyba se nezamlčí ani se nevrací původní vzor.
- Peníze, čísla a množství se formátují podle explicitně předaného locale a měny, nikoliv skládáním textu.
- Veřejné API `Api/LocaleNumberFormatter` poskytuje `formatNumber(int|float, Locale)` a `formatMoney(Money, Locale)`. Jazyk se neodvozuje z globálního kontextu a formátovač neprovádí měnové konverze.
- Peněžní částky se nikdy nepředávají do `NumberFormatter::formatCurrency()`, protože jeho vstupem je `float`. Implementace formátuje celočíselnou hlavní část přes ICU a desetinnou část sestaví přesně z `Money::minorAmount` a `Currency::minorUnits`; používá měnové prefixy, suffixy a oddělovače ICU. Neprovádí zaokrouhlování ani nemění hodnotu částky.
- Selhání ICU se propaguje jako veřejná výjimka `Api/NumberFormattingException`; nesmí se tiše vracet neformátovaná hodnota.
- Veřejné API `Api/UnitFormatter` nabízí `formatSymbol(string)` a `formatName(string, Quantity, Locale)`. Symbol je již lokalizovaná hodnota z `unit_translation`; název používá ICU MessageFormat a vzor z databáze. `Quantity::toDecimalString()` se předá jako desetinný řetězec s odstraněnými nevýznamnými koncovými nulami, nikdy jako float; interní měřítko tisícin samo nemění pluralizační kategorii.
- ICU při pluralizaci převádí číselný argument na vlastní numerickou reprezentaci. Proto `IcuUnitFormatter` předem řetězcově ověřuje absolutní hodnotu menší než `2^41`, kde rozlišení sousedních hodnot IEEE 754 zůstává menší než jedna tisícina. Nad touto hranicí se vyvolá typovaná výjimka místo potenciálně chybného plurálu; samotná `Quantity` zůstává bez omezení a bez převodu na float.
- Chybný ICU vzor, chyba formátování nebo množství mimo bezpečný rozsah ICU vyvolá veřejnou výjimku `Api/UnitFormattingException`.
- Systémové texty jsou standardně prostý text; HTML se nepovoluje bez výslovně navrženého a sanitizovaného případu.

## 6. Databázové úložiště

Obsahové překlady zůstávají v tabulkách vlastnických domén (např. `product_translation`, `category_translation`, `shipping_method_translation` a `payment_method_translation`). Systémové texty jsou rovněž fyzicky rozdělené podle vlastníka:

- Catalog: `catalog_message_translation`,
- Cart: `cart_message_translation`,
- Checkout: `checkout_message_translation`,
- Admin: `admin_message_translation`.

Každá tabulka zachovává společný kontrakt: `id`, `message_key`, `locale`, `message_text`, `source`, `is_customized`, `default_hash`, `is_outdated`, `updated_at` a `updated_by`. Sloupec `domain` není potřeba, protože vlastnictví vyjadřuje samotná tabulka. Unikátní index `(locale, message_key)` brání duplicitě klíče v rámci jazyka a vlastnické domény; `locale` odkazuje na `language(code)` s `ON DELETE RESTRICT`.

Modul Translation vlastní společnou infrastrukturu a tabulku `language`, nikoli centrální úložiště všech systémových textů. Doménové moduly vlastní své systémové klíče a jejich jazykové hodnoty. Starou tabulku `translation_message` odstranila dopředná migrace `2026-11-15-210000-split-system-message-translations.sql` až po kontrole počtu a NULL-safe shody všech přenášených sloupců. Zachována byla všechna ID i data včetně NULL hodnot `default_hash`. Historická migrace `2026-11-04-200000-init.sql` zůstává beze změny; runtime souborový fallback neexistuje.

## 7. Import a aktualizace systémových textů

Import znamená databázovou operaci, nikoliv načítání textových katalogů:

- Výchozí texty se dodávají výhradně verzovanými SQL migracemi nebo SQL seedy; každý klíč a jazykový záznam patří do tabulky svého vlastníka.
- Seed vkládá pouze chybějící dvojici `(locale, message_key)`. Použije explicitní seznam sloupců a `INSERT ... ON DUPLICATE KEY UPDATE id = id`; konflikt unikátního klíče je záměrně beze změny. Nesmí se použít `REPLACE`, `INSERT IGNORE` ani obecný upsert přepisující text či stav řádku.
- Díky tomu je seed opakovatelný a nikdy nepřepíše existující záznam, ať už jde o systémový nebo vlastní překlad. Nový systémový záznam nastaví `source = 'core'`, `is_customized = 0` a `is_outdated = 0`; `default_hash` zůstává do implementace F4-14 NULL.
- Každý seed běží v transakci. Pro více doménových tabulek se používají samostatné explicitní INSERT příkazy; název tabulky nesmí pocházet z uživatelského vstupu.
- Aktualizace již existujících systémových textů, výpočet `default_hash` a změny `is_customized` / `is_outdated` patří do F4-14. Seed F4-13 existující řádky neupravuje.
- SQL soubory smějí obsahovat výchozí hodnoty, ale nejsou runtime úložištěm. Runtime překladač čte texty výhradně z databáze; souborový fallback neexistuje.

První potvrzený katalogový systémový klíč je `button.add_to_cart` se zněními `cs` = „Přidat do košíku“ a `en` = „Add to cart“. Vlastníkem je Catalog. Seed `2026-11-17-140000-seed-catalog-add-to-cart.sql` vkládá oba jazykové řádky do `catalog_message_translation`; při existujícím klíči ponechá celý řádek beze změny. Ostatní klíče se přidávají až po definování skutečných stabilních klíčů a zdrojových znění v příslušných modulech. Testovací klíče smějí vznikat pouze v integračních testech a jejich transakce se vždy vrací zpět.

## 8. Cache a diagnostika

- Cache je pouze optimalizace; databáze zůstává jediným zdrojem pravdy.
- Systémové katalogy se ukládají přes Nette Cache do trvalého úložiště sdíleného mezi requesty a dlouho běžícími procesy.
- Klíč katalogu je jednoznačně tvořen doménou a locale; katalogy různých domén ani jazyků se nesmějí zaměnit.
- Cacheovaný `MessageStore` je dekorátor nad databázovým úložištěm. Při cache miss načte katalog z databáze a uloží jej; při cache hit neprovádí databázový dotaz.
- `MessageCacheInvalidator` je aplikační kontrakt pro invalidaci po úspěšném zápisu. Podporuje invalidaci všech katalogů, jedné domény, jednoho jazyka i jejich průniku.
- Invalidace používá tagy Nette Cache. Zapisovací operace musí invalidovat až po úspěšném potvrzení změny databáze.
- Cache se invaliduje po změně jazyků, systémových hodnot nebo vlastních překladů.
- Verzované SQL seedy se aplikují mimo běžné aplikační zapisovací služby, a proto samy neinvalidují již naplněnou Nette cache. Po migraci, která přidává nové systémové klíče do existující domény/locale, musí nasazovací postup vyčistit aplikační cache (nebo provést odpovídající řízenou invalidaci). Po vyčištění se katalog znovu načte z databáze; platí to i pro klíče výběru médií v doméně Catalog.
- Výpadek cache nesmí způsobit ztrátu nebo změnu překladových dat.

### 8.1 CLI diagnostika překladových klíčů

Příkaz `translations:check` je pouze čtecí diagnostika; nikdy nezapisuje do databáze, neopravuje ani nemaže záznamy.

- Prochází zdrojové soubory `app/UI` a hledá statické reference v Latte syntaxi `{_'stable.key'}`, v PHP voláních `$translator->translate('stable.key')` a v literálech prefixu `translations.*` používaných jako Nette Forms labely a option texty.
- Vlastnickou doménu určuje z nejbližšího presenteru, který dědí z `TranslatedPresenter` a vrací konkrétní případ `TranslationDomain` v `getTranslationDomain()`. Neodvozuje doménu z názvu klíče ani názvu adresáře.
- Každý nalezený klíč porovnává s tabulkou vlastníka a se všemi aktivními jazyky načtenými přes `LocaleProvider`. Chybějící jazyková hodnota je hlášena samostatně pro každý aktivní jazyk.
- Databázový klíč bez statické reference v odpovídající doméně je hlášen jako nepoužívaný.
- Při nálezu chybějících nebo nepoužívaných klíčů vrací příkaz nenulový návratový kód; bez nálezů končí úspěšně.
- Dynamicky sestavené klíče, reference mimo `app/UI`, obsahové překlady entit a klíče generované za běhu nelze statickým skenerem spolehlivě určit. Tyto případy vyžadují samostatné explicitní pokrytí; výsledek proto není důkazem úplné absence dynamického použití.

### 8.2 Runtime diagnostika chybějících překladů

`DatabaseTranslator` hlásí chybějící text v požadovaném locale přes `MissingTranslationReporter`, i když se podaří použít výchozí jazyk. Diagnostika deduplikuje stejné kombinace domény, locale a klíče v rámci požadavku, zapíše strukturované varování do aplikačního logu a zpřístupní záznamy ve vývojovém Tracy panelu. Panel escapuje všechny hodnoty; v produkčním režimu se nezobrazuje. Chybějící klíč se nikdy automaticky nezapisuje do databáze.

### 8.3 Admin správa a ochrana

- Read-only přehled zobrazuje všechny čtyři systémové domény a filtruje podle domény, aktivního jazyka a stavu (`core`, `custom`, upravený, zastaralý).
- Stav `upravený` a `zastaralý` se vztahuje výhradně na systémové řádky `source = core`; vlastní řádky `source = custom` se zobrazují samostatně jako vlastní zdroj, i kdyby historická data měla příznak `is_customized`. Tím se přehled změněných systémových zdrojů nemíchá s přehledem vlastních překladů.
- Vlastní překlady lze vytvořit, upravit a odstranit pouze se `source = 'custom'`. Databázový writer odmítá HTML a nikdy nezapisuje do řádku `source = 'core'`; po úspěšné změně invaliduje přesnou cache domény a locale.
- Formuláře používají výchozí CSRF ochranu Nette Forms. Zobrazení textu používá standardní escapování Latte.
- Jazyk se přidává nejprve jako neaktivní; aktivace/deaktivace je samostatná operace. Výchozí jazyk nelze deaktivovat. Aktivní jazyky se automaticky promítnou do `LocaleProvider` a jazykových rout.


## 9. Minimální ověření

- žádná překladová hodnota není v aplikačních souborech,
- runtime načítání používá pouze databázi,
- administrátor nemůže změnit ani odstranit chráněný systémový text,
- administrátor může spravovat vlastní překlad v rozsahu oprávnění,
- opakovaný seed/migrace nevytváří duplicity,
- aktualizace systémových hodnot nepřepíše vlastní překlad,
- chybějící překlad správně použije databázový fallback,
- cache se po změně invaliduje,
- domény se vzájemně nekříží,
- texty se bezpečně escapují a HTML se nepovolí bez sanitizace.

## 10. Implementační pořadí

1. Navrhnout databázové schéma systémových klíčů, jazykových hodnot a ochrany.
2. Připravit migrační/seed strategii výhradně pro databázi.
3. Implementovat MessageStore a načítání jedné domény/jazyka z DB.
4. Implementovat opakovatelný import a ochranu systémových hodnot.
5. Přidat fallback, ICU a cache.
6. Přidat admin UI pro vlastní překlady a oprávnění.
7. Přidat diagnostiku, ověření a dokumentaci.

## 11. Struktura modulu Translation

Modul používá standardní členění projektu: `Api/Dto`, `Api/Event`, `Application/Dto`, `Domain`, `Infrastructure/DI` a `Infrastructure/Persistence`. DI extension je `App\Modules\Translation\Infrastructure\DI\TranslationExtension` a Bootstrap ji automaticky registruje podle `ModuleRegistry` a konvence názvů.

Veřejné API modulu tvoří `Api/TranslationDomain`, `Api/TranslatorFactory`, `Api/MessageTranslationReader`, `Api/MessageTranslationWriter`, `Api/LocaleManager`, `Api/TranslationPermission`, `Api/TranslationDiagnostics`, `Api/TranslationMessageStatus`, `Api/LocaleNumberFormatter`, `Api/NumberFormattingException`, `Api/UnitFormatter` a `Api/UnitFormattingException`. Prezentační vrstvy pro systémové texty používají pouze `TranslationDomain` a `TranslatorFactory`; ostatní moduly mohou formátovat čísla a peníze přes `LocaleNumberFormatter`. Interní `MessageDomain` ani `DatabaseTranslatorFactory` nejsou součástí veřejného kontraktu. Interní továrna mapuje veřejnou doménu na interní enum a vrací standardní `Nette\Localization\Translator`. Modul záměrně nemá `Resources/translations` ani jiné runtime souborové katalogy. Aplikační kontrakt `MessageStore` načítá jednu vlastnickou doménu a locale do neměnného `MessageCatalog`; doménu reprezentuje `MessageDomain` a databázový adaptér `DatabaseMessageStore` vybírá tabulku pevným mapováním enumu. Každé volání provede jediný SELECT filtrovaný podle locale. Databázové `ActiveRow` se mapuje uvnitř persistence vrstvy a nikdy neopouští infrastrukturu. `DatabaseTranslator` implementuje `Nette\Localization\Translator` nad katalogem požadovaného locale a případným katalogem výchozího locale. `DatabaseTranslatorFactory` vytváří překladač pro explicitně předanou doménu a locale; výchozí locale získává z `LocaleProvider`. Překladač využívá cacheovaný `MessageStore`; chybějící klíč nejprve hledá ve výchozím katalogu stejné domény a až poté vrací stabilní klíč. ICU formátování používá locale katalogu, ze kterého byl text skutečně nalezen. Jazyk se neodvozuje ze skrytého globálního kontextu. Veškeré runtime překladové hodnoty se načítají výhradně z databáze. `DatabaseMessageTranslationReader` čte seznamy domén, `DatabaseMessageTranslationWriter` mění výhradně vlastní záznamy a invaliduje cache, `DatabaseLocaleManager` spravuje jazyky. `MissingTranslationDiagnostics` zajišťuje logování a Tracy panel. `NetteTranslationPermissionService` vynucuje přihlášené role `translation_viewer` a `translation_manager`.

## 12. Použití systémových překladů v Latte

Presenter, jehož šablony používají systémové texty, dědí z `App\Presentation\TranslatedPresenter`. Potomek výslovně vrací veřejnou `TranslationDomain`; doména se nikdy neodvozuje z názvu presenteru ani z klíče. Společný presenter používá veřejný kontrakt `TranslatorFactory`; konkrétní `DatabaseTranslatorFactory` zůstává interní implementací modulu a předá jej šabloně přes `setTranslator()`.

Jazyk se určuje z parametru `lang` aktivní routy. Před použitím se ověří přes `LocaleProvider::isActive()`; presenter bez parametru `lang` použije výchozí aktivní jazyk z databáze. Latte používá nativní překladové značky a filtr, například:

```latte
<h1>{_'catalog.title'}</h1>
<p>{_'catalog.products_count', count: $count}</p>
<span>{$messageKey|translate}</span>
```

`setTranslator()` se volá bez druhého parametru jazyka. Překlad statických klíčů proto zůstává runtime operací a respektuje databázovou cache i její invalidaci; Latte nevkládá přeložený text do jazykově specifické kompilace šablony. Výstup zůstává pod standardním escapováním Latte. Presenter, který dosud nemá schválenou vlastnickou doménu, překladač nepřebírá.

### Historie revizí

- **1.24 · 18. 11. 2026:** dokončena administrační správa vlastních překladů a jazyků, serverová oprávnění, runtime diagnostika chybějících textů s Tracy panelem a idempotentní cs/en seed domény Admin. Systémové zdroje zůstávají chráněné a všechny zápisy invalidují cache.
- **1.23 · 17. 11. 2026:** F4-17 zavádí samostatnou vlastnickou doménu `Admin` a tabulku `admin_message_translation`; administrativní texty se oddělují od obchodních domén. Doplněno mapování veřejné i interní domény, databázového úložiště a integrační test.

- **1.22 · 17. 11. 2026:** F4-16 přidává read-only příkaz `translations:check`. Porovnává statické reference v Latte/PHP pod `app/UI` s překlady ve všech aktivních jazycích a hlásí chybějící hodnoty i nepoužívané databázové klíče; dynamické reference zůstávají mimo automatickou detekci.

- **1.21 · 17. 11. 2026:** F4-14 zavádí `SystemMessageStateResolver` a neměnné `SystemMessageState`. Výchozí systémový text se aktualizuje pouze u `source = core`, `is_customized = 0` a známého předchozího `default_hash`; hash je SHA-1 přes přesné UTF-8 znění. Vlastní texty zůstávají zachovány a při změně výchozího hashe se označí jako zastaralé. Příznak zastaralosti se automaticky nemaže. Vlastní klíče (`source = custom`) zůstávají beze změny. Historické řádky s `NULL default_hash` zůstávají beze změny, protože jejich předchozí výchozí znění nelze bezpečně rekonstruovat.
- **1.20 · 17. 11. 2026:** F4-13 přidává první skutečný systémový klíč Catalog `button.add_to_cart` v češtině a angličtině do vlastnické tabulky. Seed je transakční a idempotentní; existující řádek nepřepisuje. Integrační test ověřuje i skutečná data dodaná migrací.\n- **1.19 · 17. 11. 2026:** F4-13 definuje idempotentní SQL seed pouze pro chybějící záznamy; duplicitní klíč ponechá celý existující řádek beze změny. Aktualizace systémových zdrojů je oddělena do F4-14.

- **1.16 · 17. 11. 2026:** doplněno veřejné API formátování čísel a peněz podle explicitního locale; peněžní částky zůstávají přesné v minor units bez převodu na float podle F4-10.
- **1.15 · 17. 11. 2026:** veřejné `TranslationApi` odděluje prezentační vrstvy od interního `MessageDomain` a databázové továrny; Deptrac vynucuje jednosměrnou závislost na API.\n- **1.14 · 17. 11. 2026:** F4-08 zavádí `TranslatedPresenter`, explicitní doménu a ověření aktivního locale z routy; Latte používá nativní `setTranslator()` bez compile-time locale.
- **1.13 · 17. 11. 2026:** F4-06 implementuje databázový fallback požadované locale → výchozí aktivní locale z `LocaleProvider` → stabilní klíč. Fallback zůstává izolovaný podle vlastnické domény a ICU formátování používá locale skutečně použitého katalogu.

- **1.11 · 16. 11. 2026:** F4-04 dokončen a ověřen lokálně (`All active checks passed.`) i v GitHub Actions run #336. `DatabaseTranslator` a `DatabaseTranslatorFactory` pracují s explicitní doménou a locale. Zahájena navazující etapa F4-05 pro ICU MessageFormat přes PHP `intl`.
- **1.10 · 16. 11. 2026:** implementován `DatabaseTranslator` a `DatabaseTranslatorFactory` s explicitní doménou a locale; 32 integračních testů a GitHub Actions run #336 včetně PHPStan, PHP-CS-Fixer a Deptrac úspěšně prošly. Lokální `check.bat` čeká na ověření.
- **1.9 · 16. 11. 2026:** zahájena implementace `DatabaseTranslator` a `DatabaseTranslatorFactory`; překladač dostává doménu i locale explicitně, chybějící klíč zatím vrací beze změny a ICU formátování je vyhrazeno pro F4-05.
- **1.8 · 16. 11. 2026:** dokončena implementace persistentní cache katalogů, dekorátoru `CachedMessageStore` a tagové invalidace včetně přesného průniku domény a locale; ověřeno lokálně i v GitHub Actions.

