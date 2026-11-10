# ArgoMi-BestShop – překladový subsystém

**Stav:** cílové architektonické rozhodnutí; implementace postupně  
**Revize:** 1.0 · 8. 11. 2026

## 1. Cíl

Překlady nesmí skončit v jediné neomezeně rostoucí tabulce. Budou rozdělené podle domén a modulů. Společný modul Translation bude poskytovat infrastrukturu, nikoliv vlastnit veškerý překládaný obsah.

Základní rozlišení:
1. obsahové překlady doménových entit,
2. systémové texty dodávané aplikací,
3. vlastní texty vytvořené administrátorem nebo obchodníkem.

## 2. Vlastnictví

### Obsahové překlady
Patří k modulu, který vlastní překládanou entitu:
- Catalog: produkt, kategorie, atribut, hodnota atributu, volby a další katalogový obsah,
- Shipping: názvy a popisy způsobů dopravy,
- Payments: názvy a popisy platebních metod,
- další moduly: vlastní obsahové texty podle jejich domény.

Příklady již existujících tabulek: `product_translation`, `category_translation`, `attribute_translation`, `attribute_value_translation`, `product_option_translation`, `product_option_value_translation`, `shipping_method_translation`, `payment_method_translation`, `unit_translation`.

### Systémové texty
Texty rozhraní a systémových hlášek dodává aplikace v rámci konkrétního modulu. Jejich klíče a výchozí hodnoty jsou verzované společně s kódem modulu a importují se do databáze.

Domény budou oddělené, například:
- Catalog,
- Cart,
- Checkout,
- Customer,
- Identity,
- Orders,
- Payments,
- Shipping,
- Tax,
- Inventory,
- Settings,
- Mailing.

Konkrétní fyzické rozdělení tabulek a společná metadata se navrhnou v implementačním kroku. Nesmí vzniknout jedna tabulka obsahující všechny systémové texty všech modulů.

## 3. Ochrana systémových překladů

- Systémové výchozí texty dodává aplikace a jejich zdroj je verzovaný v repozitáři.
- Administrátor je může zobrazit, vyhledat a zkontrolovat, ale nesmí přepsat chráněnou výchozí hodnotu.
- Aktualizace aplikace může přinést nové nebo změněné výchozí texty; import musí bezpečně rozpoznat novou verzi.
- Administrátorské vlastní překlady jsou samostatné záznamy/override s jasným vlastníkem a oprávněním.
- Vlastní překlad nesmí měnit systémový zdroj. Musí být možné ho odstranit nebo vrátit na výchozí text.
- Přístup se vynucuje v aplikační vrstvě i databázových pravidlech, kde to dává smysl. Pouhé skrytí tlačítka v UI není ochrana.

## 4. Vlastní překlady a oprávnění

Administrátor může podle oprávnění:
- vytvořit vlastní text v povolené doméně,
- přidat překlad do aktivního jazyka,
- upravit vlastní obsahové překlady,
- spravovat překlady, které obchodník vlastní.

Administrátor nesmí:
- změnit systémový výchozí text dodaný aplikací,
- přepsat identifikátor systémového klíče,
- odstranit systémový klíč tak, aby jej aktualizace aplikace nemohla obnovit,
- měnit překlady mimo rozsah svého oprávnění.

Role a oprávnění spravuje Identity. Modul Translation poskytuje kontrolní body a auditní události.

## 5. Fallback a formátování

- Výchozí jazyk instalace je čeština (`cs`), další počáteční jazyk angličtina (`en`).
- Chybějící překlad se řeší fallbackem na výchozí jazyk a poté na klíč s diagnostikou; nikdy se nemá tiše vracet prázdný text.
- Parametrizované zprávy používají ICU MessageFormat přes PHP `intl`.
- Peníze, čísla a množství se formátují podle locale a měny, nikoliv skládáním textu.
- Systémové texty jsou standardně prostý text; HTML se nepovoluje bez výslovně navrženého a sanitizovaného případu.

## 6. Zdrojové soubory a import

Každý modul udržuje své výchozí systémové texty v `Modules/<Module>/Resources/translations`. Modul Translation zajišťuje:
- načtení domény a jazyka,
- import a aktualizaci systémových klíčů,
- kontrolu chybějících a nepoužitých klíčů,
- detekci změněných výchozích textů,
- cache a invalidaci po změně vlastního překladu,
- logování chybějících překladů,
- diagnostiku pro Tracy ve vývojovém prostředí.

Import musí být opakovatelný a nesmí přepsat chráněný zdroj vlastním textem administrátora.

## 7. Formát zdrojových souborů

Výchozí systémové texty jsou uloženy v JSON souborech podle modulu a jazyka:

```text
app/Modules/<Module>/Resources/translations/<locale>.json
```

Kořenem souboru je JSON objekt, jehož vlastnosti jsou stabilní klíče a hodnoty jsou neprázdné prosté texty. Klíče používají zápis s tečkami, například `product.not_found`. Jeden soubor obsahuje vždy jeden jazyk; počáteční locale jsou `cs` a `en`. HTML se ve zdrojových systémových překladech nepovoluje. Parametrizované zprávy a ICU MessageFormat se doplní v navazující implementaci.

Načítání prochází explicitní seznam modulů z `ModuleRegistry`, který předává kompoziční vrstva. Modul Translation nesmí přímo záviset na Core. Chybějící soubor znamená, že modul pro daný jazyk zatím žádné výchozí texty nedodává; existující, ale nečitelný nebo neplatný soubor je chyba a nesmí být tiše ignorován.

## 8. Současný stav databáze a migrace

Současné schéma v1 obsahuje:
- doménové překladové tabulky (např. `product_translation`, `category_translation`, `shipping_method_translation`, `payment_method_translation`),
- centrální tabulku `translation_message` pro systémové texty.

Centrální `translation_message` je **přechodný stav**, nikoliv cílové řešení. Historický soubor `2026-11-04-200000-init.sql` se nemění. Rozdělení systémových textů se provede novou dopřednou migrací po návrhu cílového schématu.

Před migrací:
1. inventarizovat klíče a jejich domény,
2. rozhodnout fyzické tabulky nebo oddělené úložiště po modulech,
3. navrhnout vazbu mezi systémovým zdrojem a administrátorským override,
4. zajistit unikátnost klíče v rámci domény a jazyka,
5. připravit převod dat bez ztráty a test porovnání počtu/obsahu,
6. teprve poté odstranit staré úložiště novou migrací.

## 9. Minimální testy

- administrátor nemůže změnit systémový výchozí text,
- administrátor může přidat vlastní překlad v povolené doméně,
- import nového vydání nepřepíše vlastní překlad,
- změněný systémový text je detekován jako aktualizovaný,
- chybějící překlad správně použije fallback,
- cache se po změně invaliduje,
- domény se vzájemně nekříží,
- neznámý nebo nepoužitý klíč je diagnostikován,
- texty se bezpečně escapují a HTML se nepovolí bez sanitizace.

## 10. Implementační pořadí

1. Navrhnout cílové schéma a doménové hranice.
2. Připravit migrační plán ze současného `translation_message`.
3. Implementovat MessageStore a načítání jedné domény/jazyka.
4. Implementovat import a ochranu systémových textů.
5. Přidat fallback, ICU a cache.
6. Přidat admin UI pro vlastní překlady a oprávnění.
7. Přidat diagnostiku, testy a dokumentaci.

