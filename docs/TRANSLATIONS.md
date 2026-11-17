# ArgoMi-BestShop – překladový subsystém

**Stav:** závazný architektonický standard; implementace postupně  
**Revize:** 1.16 · 17. 11. 2026

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

Role a oprávnění spravuje Identity. Modul Translation poskytuje kontrolní body a auditní události.

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
- Systémové texty jsou standardně prostý text; HTML se nepovoluje bez výslovně navrženého a sanitizovaného případu.

## 6. Databázové úložiště

Obsahové překlady zůstávají v tabulkách vlastnických domén (např. `product_translation`, `category_translation`, `shipping_method_translation` a `payment_method_translation`). Systémové texty jsou rovněž fyzicky rozdělené podle vlastníka:

- Catalog: `catalog_message_translation`,
- Cart: `cart_message_translation`,
- Checkout: `checkout_message_translation`.

Každá tabulka zachovává společný kontrakt: `id`, `message_key`, `locale`, `message_text`, `source`, `is_customized`, `default_hash`, `is_outdated`, `updated_at` a `updated_by`. Sloupec `domain` není potřeba, protože vlastnictví vyjadřuje samotná tabulka. Unikátní index `(locale, message_key)` brání duplicitě klíče v rámci jazyka a vlastnické domény; `locale` odkazuje na `language(code)` s `ON DELETE RESTRICT`.

Modul Translation vlastní společnou infrastrukturu a tabulku `language`, nikoli centrální úložiště všech systémových textů. Doménové moduly vlastní své systémové klíče a jejich jazykové hodnoty. Starou tabulku `translation_message` odstranila dopředná migrace `2026-11-15-210000-split-system-message-translations.sql` až po kontrole počtu a NULL-safe shody všech přenášených sloupců. Zachována byla všechna ID i data včetně NULL hodnot `default_hash`. Historická migrace `2026-11-04-200000-init.sql` zůstává beze změny; runtime souborový fallback neexistuje.

## 7. Import a aktualizace systémových textů

Import znamená databázovou operaci, nikoliv načítání textových katalogů:
- nové systémové klíče a jazykové hodnoty se vkládají řízeným DB seedem/migrací; SQL soubory smějí obsahovat výchozí hodnoty,
- aktualizace porovnává verzi nebo hash systémového záznamu,
- chráněné systémové hodnoty se aktualizují pouze autorizovaným migračním mechanismem,
- administrátorské vlastní hodnoty se nikdy nepřepisují,
- operace musí být opakovatelná, transakční a auditovatelná,
- runtime překladač čte texty výhradně z databáze; souborový fallback neexistuje.

## 8. Cache a diagnostika

- Cache je pouze optimalizace; databáze zůstává jediným zdrojem pravdy.
- Systémové katalogy se ukládají přes Nette Cache do trvalého úložiště sdíleného mezi requesty a dlouho běžícími procesy.
- Klíč katalogu je jednoznačně tvořen doménou a locale; katalogy různých domén ani jazyků se nesmějí zaměnit.
- Cacheovaný `MessageStore` je dekorátor nad databázovým úložištěm. Při cache miss načte katalog z databáze a uloží jej; při cache hit neprovádí databázový dotaz.
- `MessageCacheInvalidator` je aplikační kontrakt pro invalidaci po úspěšném zápisu. Podporuje invalidaci všech katalogů, jedné domény, jednoho jazyka i jejich průniku.
- Invalidace používá tagy Nette Cache. Zapisovací operace musí invalidovat až po úspěšném potvrzení změny databáze.
- Cache se invaliduje po změně jazyků, systémových hodnot nebo vlastních překladů.
- Výpadek cache nesmí způsobit ztrátu nebo změnu překladových dat.

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

Veřejné API modulu tvoří `Api/TranslationDomain`, `Api/TranslatorFactory`, `Api/LocaleNumberFormatter` a `Api/NumberFormattingException`. Prezentační vrstvy pro systémové texty používají pouze `TranslationDomain` a `TranslatorFactory`; ostatní moduly mohou formátovat čísla a peníze přes `LocaleNumberFormatter`. Interní `MessageDomain` ani `DatabaseTranslatorFactory` nejsou součástí veřejného kontraktu. Interní továrna mapuje veřejnou doménu na interní enum a vrací standardní `Nette\Localization\Translator`. Modul záměrně nemá `Resources/translations` ani jiné runtime souborové katalogy. Aplikační kontrakt `MessageStore` načítá jednu vlastnickou doménu a locale do neměnného `MessageCatalog`; doménu reprezentuje `MessageDomain` a databázový adaptér `DatabaseMessageStore` vybírá tabulku pevným mapováním enumu. Každé volání provede jediný SELECT filtrovaný podle locale. Databázové `ActiveRow` se mapuje uvnitř persistence vrstvy a nikdy neopouští infrastrukturu. `DatabaseTranslator` implementuje `Nette\Localization\Translator` nad katalogem požadovaného locale a případným katalogem výchozího locale. `DatabaseTranslatorFactory` vytváří překladač pro explicitně předanou doménu a locale; výchozí locale získává z `LocaleProvider`. Překladač využívá cacheovaný `MessageStore`; chybějící klíč nejprve hledá ve výchozím katalogu stejné domény a až poté vrací stabilní klíč. ICU formátování používá locale katalogu, ze kterého byl text skutečně nalezen. Jazyk se neodvozuje ze skrytého globálního kontextu. Veškeré runtime překladové hodnoty se načítají výhradně z databáze.

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

- **1.15 · 17. 11. 2026:** veřejné `TranslationApi` odděluje prezentační vrstvy od interního `MessageDomain` a databázové továrny; Deptrac vynucuje jednosměrnou závislost na API.\n- **1.14 · 17. 11. 2026:** F4-08 zavádí `TranslatedPresenter`, explicitní doménu a ověření aktivního locale z routy; Latte používá nativní `setTranslator()` bez compile-time locale.
- **1.13 · 17. 11. 2026:** F4-06 implementuje databázový fallback požadované locale → výchozí aktivní locale z `LocaleProvider` → stabilní klíč. Fallback zůstává izolovaný podle vlastnické domény a ICU formátování používá locale skutečně použitého katalogu.

- **1.11 · 16. 11. 2026:** F4-04 dokončen a ověřen lokálně (`All active checks passed.`) i v GitHub Actions run #336. `DatabaseTranslator` a `DatabaseTranslatorFactory` pracují s explicitní doménou a locale. Zahájena navazující etapa F4-05 pro ICU MessageFormat přes PHP `intl`.
- **1.10 · 16. 11. 2026:** implementován `DatabaseTranslator` a `DatabaseTranslatorFactory` s explicitní doménou a locale; 32 integračních testů a GitHub Actions run #336 včetně PHPStan, PHP-CS-Fixer a Deptrac úspěšně prošly. Lokální `check.bat` čeká na ověření.
- **1.9 · 16. 11. 2026:** zahájena implementace `DatabaseTranslator` a `DatabaseTranslatorFactory`; překladač dostává doménu i locale explicitně, chybějící klíč zatím vrací beze změny a ICU formátování je vyhrazeno pro F4-05.
- **1.8 · 16. 11. 2026:** dokončena implementace persistentní cache katalogů, dekorátoru `CachedMessageStore` a tagové invalidace včetně přesného průniku domény a locale; ověřeno lokálně i v GitHub Actions.


| 17. 11. 2026 | v1.16: doplněno veřejné API formátování čísel a peněz podle explicitního locale; peněžní částky zůstávají přesné v minor units bez převodu na float podle F4-10. |
