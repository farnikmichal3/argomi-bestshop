# ArgoMi-BestShop – překladový subsystém

**Stav:** závazný architektonický standard; implementace postupně  
**Revize:** 1.2 · 10. 11. 2026

## 1. Základní pravidlo

**Databáze je jediným zdrojem pravdy pro veškerý překládaný text. Bez výjimek.**

V databázi musí být uloženo každé jazykové znění:
- systémových textů a hlášek dodávaných aplikací,
- obsahových údajů doménových entit,
- vlastních textů vytvořených administrátorem nebo obchodníkem,
- názvů, popisů, validačních zpráv, stavů a textů uživatelského rozhraní.

Překladové hodnoty nesmějí být uloženy v PHP, Latte, JSON, NEON, YAML, XML, JavaScriptu, CSS/SCSS ani v jiných souborech. Nesmějí existovat souborové katalogy, runtime fallback soubory ani kopie výchozích překladů v repozitáři. V kódu smějí být pouze stabilní klíče/identifikátory a technická logika.

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
- Výchozí hodnoty se do databáze dostávají výhradně řízenou databázovou migrací nebo databázovým seedem. Seed nesmí vytvářet souborové katalogy ani je za běhu načítat.
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
- Chybějící překlad se řeší databázovým fallbackem na výchozí jazyk a poté na klíč s diagnostikou; nikdy se nevrací souborová hodnota ani prázdný text.
- Parametrizované zprávy používají ICU MessageFormat přes PHP `intl`; samotné vzory zpráv jsou uložené v databázi.
- Peníze, čísla a množství se formátují podle locale a měny, nikoliv skládáním textu.
- Systémové texty jsou standardně prostý text; HTML se nepovoluje bez výslovně navrženého a sanitizovaného případu.

## 6. Databázové úložiště

Současné schéma v1 obsahuje:
- doménové překladové tabulky (např. `product_translation`, `category_translation`, `shipping_method_translation`, `payment_method_translation`),
- centrální tabulku `translation_message` pro systémové texty.

Tabulka `translation_message` je současným databázovým úložištěm systémových textů. Není dovoleno ji obcházet souborovými katalogy. Případné budoucí rozdělení systémových tabulek podle domén je možné pouze novou dopřednou migrací, která zachová všechna data. Historický soubor `2026-11-04-200000-init.sql` se nemění.

Před případnou migrací:
1. inventarizovat klíče a jejich domény,
2. navrhnout cílové tabulky nebo bezpečné doménové rozdělení,
3. navrhnout vazbu systémových hodnot a vlastních překladů,
4. zajistit unikátnost klíče v rámci domény a jazyka,
5. připravit převod dat bez ztráty a kontrolu počtu i obsahu,
6. odstranit staré úložiště až novou dopřednou migrací.

## 7. Import a aktualizace systémových textů

Import znamená databázovou operaci, nikoliv načítání textových katalogů:
- nové systémové klíče a jazykové hodnoty se vkládají řízeným DB seedem/migrací,
- aktualizace porovnává verzi nebo hash systémového záznamu,
- chráněné systémové hodnoty se aktualizují pouze autorizovaným migračním mechanismem,
- administrátorské vlastní hodnoty se nikdy nepřepisují,
- operace musí být opakovatelná, transakční a auditovatelná,
- runtime překladač čte texty výhradně z databáze; souborový fallback neexistuje.

## 8. Cache a diagnostika

- Cache je pouze optimalizace; databáze zůstává jediným zdrojem pravdy.
- Cache se invaliduje po změně jazyků, systémových hodnot nebo vlastních překladů.
- Chybějící, neznámé a nepoužité klíče se diagnostikují; ve vývojovém prostředí také přes Tracy.
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
