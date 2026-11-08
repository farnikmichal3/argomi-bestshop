# ArgoMi-bestshop – záměr a rozhodnutí

Verze dokumentu 0.1 · 2. 11. 2026 · výchozí bod pro všechna další rozhodnutí

**Stavy rozhodnutí v tomto dokumentu**

- **[ROZHODNUTO]**: potvrdil autor projektu.
- **[NAVRŽENO]**: doporučení, které zatím nebylo výslovně potvrzeno.
- **[OTEVŘENO]**: čeká na rozhodnutí.

---

## 1. Vize

ArgoMi-bestshop je nový e-shop systém postavený na **nejnovějším Nette frameworku**. Ambicí je nabídnout základ srovnatelný s PrestaShopem a přidat do jádra mnoho funkcí (zhruba stovku), za které se dnes jinde platí moduly (např. pobídka dopravy zdarma). Každá nová funkce přijde s aktualizací a každý ji může hned využít. **[ROZHODNUTO]**

Systém se vyvíjí a předvádí s demo daty ve dvou jazycích (čeština výchozí, angličtina) a dvou měnách (CZK výchozí, EUR). Podrobnosti v kapitole 8.

Hlavní hodnoty, v tomto pořadí:

1. **Bezpečnost.**
2. **Čitelnost a kvalita kódu** (PHPStan level max, striktní DI).
3. **Výukový charakter**: projekt je zároveň studijní materiál.
4. **Úplná sada funkcí v jádře**, bez instalace modulů třetích stran.

## 2. Distribuce a licence

- **Provoz jako služba:** každý zájemce dostane obchod **zdarma na hostingu autora**. **[ROZHODNUTO]**
- **Studenti** dostanou zdrojový kód verze **1.0.0 stable** pro vlastní studijní využití, **bez práva ho šířit**. **[ROZHODNUTO]**
- Projekt **není open source** v pravém slova smyslu (zdrojový kód není veřejný a nelze ho svobodně šířit). V komunikaci se tedy nemá používat označení "open source". **[ROZHODNUTO]**
- Konkrétní text licence (studentská licence, podmínky služby) **[OTEVŘENO]**. Výchozí stav do rozhodnutí: soukromý repozitář, všechna práva vyhrazena. Před spuštěním konzultovat s právníkem.
- U cizích příspěvků do kódu zavést souhlas s licencováním (CLA), jinak bude pozdější změna licence složitá. **[NAVRŽENO]**
- Hostování cizích obchodů se zákaznickými daty znamená, že autor je **zpracovatelem osobních údajů** (GDPR): je potřeba smlouva o zpracování a podmínky služby. **[OTEVŘENO]**, konzultovat s právníkem.

## 3. Architektura

### 3.1 Modulární monolit

- Aplikace je **jedna** (jedno nasazení), uvnitř rozdělená na **moduly** (Catalog, Pricing, Inventory, Cart, Checkout, Orders, Shipping, Payments, Tax, Customer, Fulfillment, Compliance, Vouchers, ...). **[ROZHODNUTO]**
- **Žádný instalátor modulů třetích stran.** Moduly slouží jen k čitelnosti kódu. **[ROZHODNUTO]**
- Rozšiřitelnost řeší **rozhraní a nastavení v administraci**, ne cizí kód (např. rozhraní dopravce, platební brány, doménové události). **[NAVRŽENO]**

### 3.2 Hranice modulů

- Modul má **veřejné API** (fasáda, DTO, události) a **vnitřek**, který ostatní nesmí používat. **[NAVRŽENO]**
- Hranice vynucovat automaticky v CI (PHPStan pravidla, `phpat` nebo `deptrac`). **[NAVRŽENO]**
- Komunikace: synchronně přes fasády, asynchronně přes doménové události (`OrderPlaced`, `PaymentConfirmed`, ...). **[NAVRŽENO]**
- Struktura složek **po modulech, ne po vrstvách**: `src/<Modul>/{Domain,Application,Infrastructure,UI}`. **[NAVRŽENO]**
- Každý modul má vlastní DI extension. **[NAVRŽENO]**

### 3.3 Dependency Injection

- **Striktní DI vždy:** konstruktorová injekce, žádný service locator, žádné statické přístupy. **[ROZHODNUTO]**
- Formuláře a základní logika UI v presenterech podle Nette konvencí. **[ROZHODNUTO]**

### 3.4 Provozní model

- **Jedna instance a jedna databáze na obchod.** Žádné `shop_id` v tabulkách, žádný multi-tenant. **[ROZHODNUTO]**
- Důsledek: migrace je nutné spouštět nad všemi databázemi, zálohy a monitoring řešit pro každou instanci. Je potřeba nástroj pro hromadné nasazení a aktualizace. **[OTEVŘENO]**

## 4. Technologie

| Oblast | Rozhodnutí | Stav |
|---|---|---|
| Framework | Nejnovější Nette | ROZHODNUTO |
| Šablony | Latte | ROZHODNUTO (součást Nette) |
| Databáze | **MySQL** (doporučeno min. 8.0.16), InnoDB, utf8mb4 | ROZHODNUTO |
| Přístup k datům | **Nette Database Explorer** | ROZHODNUTO |
| Statická analýza | **PHPStan level max** od prvního commitu | ROZHODNUTO |
| Front-end | **Bootstrap**, **npm + Sass**, vlastní build skripty | ROZHODNUTO |
| Bundler | **Bez Vite.** Vlastní buildy, skripty v `package.json` (`npm run build`, `npm run watch`) | ROZHODNUTO |
| Nástroje | Zdarma (Bootstrap, Laragon, VS Code, ...) | ROZHODNUTO |
| AJAX | **Naja** | ROZHODNUTO (ověřeno: udržovaná, MIT, aktuální 3.x) |
| Admin tabulky | **Datagrid (contributte/ublaboo)** | ROZHODNUTO |
| Migrace | **Nextras Migrations** (čisté SQL) | ROZHODNUTO |
| Vyhledávání | Rozhraní `ProductSearch`, výchozí implementace nad MySQL `FULLTEXT`, **Elasticsearch/OpenSearch jako volitelná implementace** | NAVRŽENO |
| Vývojové prostředí | Laragon je jen pro Windows; doplnit Docker nebo DDEV pro ostatní | NAVRŽENO |
| Testy a CI | Nette Tester nebo PHPUnit, GitHub Actions (PHPStan, coding standard, testy) | NAVRŽENO |

### Pravidla pro přijímání závislostí (rozšíření)

Požadavek: **zdarma, moderní a stabilní**. **[ROZHODNUTO]** Pro každou knihovnu zkontrolovat:

1. Poslední release a podpora aktuálních verzí PHP a Nette.
2. Počet udržovatelů (jeden člověk = riziko).
3. Kompatibilní licence.
4. Každé rozšíření za **tenkým adaptérem**, aby šlo vyměnit.
5. Přesné verze v `composer.lock`, aktualizace přes CI s testy.

Poznámka k Datagridu: je nejrozšířenější, ale autoři sami uvádějí omezený čas na bezplatnou údržbu. Proto jen v administraci a bez doménové logiky v něm. **[NAVRŽENO]**

U každého rozhodnutí o knihovně psát **ADR** (krátký záznam: co, proč, alternativy). **[NAVRŽENO]**

## 5. Bezpečnost

Bezpečnost je hlavní hodnota projektu. **[ROZHODNUTO]**

- **CSP** (Content Security Policy) s nonce, bez `unsafe-inline`. **[ROZHODNUTO]**
- **Žádné `noescape`** v šablonách. Latte escapuje podle kontextu. Pravidlo prosadit v code review nebo vlastním pravidlem analýzy. **[ROZHODNUTO]**
- **Validace na vstupu, escapování na výstupu.** V databázi jsou **surová data**, ne předescapovaná. **[ROZHODNUTO]**
- HTML povolené v obsahu (popisy produktů) čistit při uložení knihovnou typu **HTMLPurifier**. **[ROZHODNUTO / ověřit konkrétní knihovnu]**
- **Ochrana na úrovni databázové vrstvy:** vždy parametrizované dotazy, nikdy skládání SQL z řetězců. **[ROZHODNUTO]**
- Doplnit: ochrana proti CSRF (formuláře a signály v Nette), hashování hesel přes `Passwords`, omezení počtu pokusů o přihlášení, bezpečné session, logování bez osobních údajů, GDPR. **[NAVRŽENO]**
- Platby výhradně přes bránu. **Data karet se neukládají**, jen tokeny od brány. **[NAVRŽENO]**
- Soubory nahrané zákazníkem: uložit mimo veřejný webroot, ověřit MIME na serveru, náhodný název, nikdy nespouštět. **[NAVRŽENO]**

## 6. Databáze

### 6.1 Rozhodnutí

1. **Migrace:** Nextras Migrations. **[ROZHODNUTO]**
2. **Atributy:** relační tabulky s typovanými sloupci pro to, co se filtruje (číslo, jednotka, výčet), JSON jen pro čistě zobrazované údaje. **[ROZHODNUTO]**
3. **Primární klíče:** `BIGINT UNSIGNED AUTO_INCREMENT` interně, navíc nezaměnitelné veřejné ID (ULID) u objednávek, košíků, zákazníků, licencí a poukazů. **[ROZHODNUTO]**
4. **Překlady a měny** (podrobně kapitola 8) v modelu hned (překladové tabulky, měna u každé ceny), i když první verze poběží s jedním jazykem. **[ROZHODNUTO]**
5. **Bez multistore** v rámci jedné instalace. **[ROZHODNUTO]**
6. **Sklad notebooku:** podporovat obě možnosti (sklad komponent i hotových konfigurací) přes přepínač `stock_mode` a strategie v kódu. Každá možnost se zapne až s testy. Sloupec přibude v migraci vlny 4 (konfigurátor), ve vlně 1 záměrně není. **[ROZHODNUTO]**
7. **Sleva u sady:** podporovat obě možnosti (`at_purchase` / `at_return`) přes přepínač `discount_allocation`. Výchozí `at_purchase`. Patří k sadě (tabulka `bundle`, vlna 2), ne k tabulce `product`. **[ROZHODNUTO]**

### 6.2 Konvence pro Nette Database Explorer

- Každá tabulka má PK `id`, i spojovací.
- Názvy tabulek v jednotném čísle, anglicky, `snake_case`.
- Cizí klíč se jmenuje `<název_tabulky>_id` (např. `product_variant_id`).
- Výjimky vyžadují explicitní sloupec v `ref()` (např. `category.parent_id`).
- `order` je rezervované slovo, proto tabulka `shop_order`.
- `ActiveRow` **neopouští** repozitář modulu. Ven jdou typované DTO nebo value objecty (kvůli PHPStan level max a hranicím modulů). **[NAVRŽENO]**

### 6.3 Obecná pravidla

- **Peníze:** celá čísla v nejmenších jednotkách plus měna. Nikdy `FLOAT`.
- **Množství:** `DECIMAL(12,3)`.
- **Čas:** ukládat v UTC.
- Uvnitř modulu skutečné cizí klíče, **mezi moduly jen ID bez cizího klíče**.
- **Objednávka je snímek** okamžiku nákupu (názvy, ceny, sazby DPH, volby i adresy se kopírují). Základní cena varianty (`base_unit_price_minor`) a součet příplatků za volby (`options_unit_total_minor`) jsou v řádku objednávky **samostatné sloupce**.
- **MySQL a `CHECK`:** omezení `CHECK` nelze použít nad sloupci, které mají cizí klíč s akcí `CASCADE` nebo `SET NULL`. Takové pravidlo se hlídá v aplikaci (např. produkt nesmí odkazovat sám na sebe).
- **Názvy omezení** psát celými slovy (`fk_product_attribute_value_attribute_value`), ne zkratkami, ať jsou srozumitelné studentům. Limit je 64 znaků.
- **Stav objednávky** jako explicitní stavový automat v PHP.
- **Rezervace skladu** odděleně od skutečného odpisu.
- Citlivá data (licenční klíče, soubory, ověření věku) mají vlastní tabulky, minimum údajů a lhůtu pro smazání.
- Migrace psát od prvního dne a testovat v CI.

### 6.4 Schéma

Schéma vlny 1 je v souboru `001_wave1_structure.sql` (moduly Tax, Catalog, Pricing, Inventory, Customer, Shipping, Payments, Cart, Orders). Je to návrh, který zatím nebyl spuštěn na databázi. **[NAVRŽENO]**

## 7. Demo produkty (zkušební případy pro model dat)

| # | Produkt | Mechanismus | Vlna |
|---|---|---|---|
| 1 | Tričko (barva, velikost, potisk) | Varianty + volba zákazníka | 1 |
| 2 | USB-C kabel (délka, barva, počet C koncovek) | Varianty + typované atributy | 1 |
| 3 | GaN nabíječka (sloty, výkon, barva) | Varianty + kompatibilita | 1 |
| 8 | Herní set (myš + podložka + sluchátka) | Sada (bundle) | 2 |
| 9 | Kancelářský papír (od 10 ks / od 50 ks) | Množstevní slevy | 2 |
| 10 | Novinka před vydáním / vyprodaná položka | Předobjednávka + hlídací pes | 2 |
| 11 | Látka nebo kabel z role | Neceločíselné množství | 2 |
| 4 | Dlaždice (velikost, barva, vzor) | Jednotka m², balení, šarže | 3 |
| 6 | Antivirus (domácí/firemní, doba trvání) | Digitální produkt, licence | 3 |
| 7 | Alkohol / tabák / erotické pomůcky | Věkové omezení | 3 |
| 14 | Nápoj se zálohou na láhev | Vratná záloha | 3 |
| 15 | Dárkový poukaz | Poukaz | 3 |
| 5 | Notebook (HDD, RAM, barva, značka) | Konfigurátor | 4 |
| 12 | Hrnek s vlastní fotkou nebo gravírováním | Produkt na míru s uploadem | 4 |
| 13 | Roční licence s automatickou obnovou | Předplatné | 4 |

Sada a pořadí vln jsou **[ROZHODNUTO]**. U věkově omezeného zboží (alkohol, tabák) a u digitálních služeb (DPH podle země, OSS) je před spuštěním nutná právní konzultace.

### Průřezové vlastnosti produktu (capabilities)

Produkt nese příznaky, které čtou ostatní moduly (např. Checkout se ptá "vyžaduje ověření věku?", ne "je to alkohol?"): jednotka a množství, typ doručení, omezení, způsob sledování skladu, způsob výpočtu ceny, daňový režim, personalizace, opakování. **[NAVRŽENO]**

## 8. Demo data, jazyky a měny

### Rozhodnuto

- Systém se vyvíjí a předvádí **s demo daty**: 15 demo produktů z kapitoly 7 plus kategorie, atributy, způsoby dopravy a platby, zákazníci a ukázkové objednávky. **[ROZHODNUTO]**
- Demo data existují ve **dvou jazycích**: **čeština (výchozí)** a **angličtina**. **[ROZHODNUTO]**
- Demo data existují ve **dvou měnách**: **česká koruna CZK (výchozí)** a **euro EUR**. **[ROZHODNUTO]**
- **Veškeré překlady jsou v databázi** a každý administrátor je může vytvářet i upravovat. Překlady jsou rozdělené na části podle toho, k čemu patří, ne v jedné velké tabulce (viz podkapitola Překlady). **[ROZHODNUTO]**

### Navržená pravidla

1. **Kódy:** jazyky `cs` a `en`, měny podle ISO 4217 (`CZK`, `EUR`). Výchozí jazyk a výchozí měna jsou **nastavení instance**, v kódu nesmí být napevno. **[NAVRŽENO]**
2. **Překlady a záloha:** překlad do výchozího jazyka je povinný, ostatní jsou volitelné. Chybí-li překlad, použije se výchozí jazyk (nikdy prázdné pole nebo chyba). **[NAVRŽENO]**
3. **Ceny jsou zadané výslovně pro každou měnu** (ceník v CZK a ceník v EUR), nejsou přepočítávané kurzem za běhu. Obchodník chce cenu 24,99 €, ne 24,6371 €. Přepočet kurzem může později být jen nástroj, který navrhne ceny k potvrzení. **[NAVRŽENO]**
4. **Jedna měna na košík a objednávku.** Po vytvoření objednávky se měna nemění. Objednávka si pamatuje měnu i jazyk. **[NAVRŽENO]**
5. **Měna a země DPH jsou dvě různé věci.** Euro se používá v mnoha zemích, každá má jiné sazby, a zákazník platící v eurech nemusí být z jiné země. Demo data počítají s českým DPH. Pravidla podle země dodání (včetně OSS) patří do vlny 3. **[NAVRŽENO]**
6. **Prahy a poplatky jsou po měnách.** Například pobídka dopravy zdarma má jiný limit v CZK (např. 2 000 Kč) a v EUR (např. 80 €), stejně tak cena dopravy a poplatek za platbu. **[NAVRŽENO]**
7. **Formátování čísel a peněz přes rozšíření `intl`** podle jazyka (`1 234,50 Kč` vs. `€1,234.50`), nikdy ruční skládání řetězců. **[NAVRŽENO]**
8. **Texty rozhraní** (tlačítka, chyby, e-maily) nejsou nikdy napevno v šabloně. Jsou v databázi a spravují se podle podkapitoly Překlady. **[NAVRŽENO]**
9. **Testy běží pro obě kombinace** jazyka a měny. Student tak vidí, jak vypadá typická chyba (napevno napsané "Kč", ruční formát čísla). **[NAVRŽENO]**

### Překlady: architektura

Překlady se dělí na tři druhy. Každý druh má vlastní místo v databázi.

| Druh | Příklady | Kde je |
|---|---|---|
| **Obsah entit** | popis produktu, název kategorie, text článku v blogu, stránka CMS, název a popis akce, název stavu objednávky, šablona e-mailu | překladová tabulka u každé entity **ve vlastním modulu** (`product_translation`, `cms_page_translation`, `blog_post_translation`, `promotion_translation`, ...) |
| **Jednotky** | kg, m, W, ks, m² | `unit` a `unit_translation` (modul Core) |
| **Systémové texty** | tlačítka, chybové hlášky, texty v košíku a pokladně, e-maily, administrace | `translation_message`, rozdělené sloupcem `domain` (`checkout`, `catalog`, `account`, `emails`, `admin`, `validation`, ...) |

**Navržená pravidla**

1. **Každý modul vlastní překlady své entity** a deklaruje své domény systémových textů. Nový modul (blog, CMS, akce) přináší vlastní překladové tabulky, nepřidává se do centrální tabulky. **[NAVRŽENO]**
2. **Objednávka se nepřekládá.** Je snímek v jazyce zákazníka z okamžiku nákupu. Překládá se jen to, co k ní patří okolo (názvy stavů, šablony e-mailů). **[NAVRŽENO]**
3. **Tabulka `translation_message`:** `domain`, `message_key`, `locale`, `message_text`, `source` (`core` nebo `custom`), `is_customized`, `default_hash`, `is_outdated`, `updated_at`, `updated_by`. Unikátní klíč `(domain, locale, message_key)`. Jedna tabulka rozdělená doménou, ne fyzicky oddělené tabulky po modulech. Sto tisíc řádků pro MySQL s tímto indexem není problém. Doména řeší přehlednost a načítání, ne rychlost. **[ROZHODNUTO]**
4. **Za běhu se nečtou jednotlivé fráze z databáze.** Překladač načte jednou celou doménu v jednom jazyce do cache (Nette Cache). Uložení v administraci zneplatní cache jen pro danou doménu a jazyk. **[NAVRŽENO]**
5. **Záloha při chybějícím překladu:** požadovaný jazyk, pak výchozí jazyk, pak klíč. Chybějící překlady se logují a ve vývojovém režimu jsou vidět v Tracy. **[NAVRŽENO]**
6. **Výchozí texty dodává jádro** (verzované soubory v modulu, testované) a při nasazení se importují do databáze. Administrátor je může přepsat: řádek dostane `is_customized = 1` a aktualizace ho nepřepíše. Když se výchozí text jádra později změní, importér řádek označí `is_outdated = 1` a administrátor rozhodne, zda převezme nový výchozí text, nebo ponechá svůj. Díky tomu nová funkce přijde s hotovými českými a anglickými texty a úpravy administrátora zůstanou. **[ROZHODNUTO]**
7. **Formát textů je ICU MessageFormat** (přes rozšíření `intl`, které už vyžadujeme). Jedna zpráva zvládne množná čísla (čeština má tvary pro 1, 2 až 4 a 5 a více). `unit_translation` obsahuje název a symbol jednotky, včetně množných tvarů. **[NAVRŽENO]**
8. **Bezpečnost:** systémové texty jsou prostý text a vždy se escapují. HTML se v nich nepovoluje, odkaz se řeší zástupným symbolem. Pole s formátovaným obsahem (popis produktu, CMS, blog) čistí HTMLPurifier při uložení. Právo upravovat překlady je samostatné oprávnění rolí v administraci a ukládá se, kdo a kdy změnu provedl. **[NAVRŽENO]**
9. **Nový jazyk** přidá administrátor v administraci (záznam v `language`) a překlady doplní postupně. Do doplnění platí záloha. **[NAVRŽENO]**

### Dopad na schéma (změny oproti verzi 1)

| Oblast | Změna | Proč |
|---|---|---|
| `shipping_method` | Cena přesunout do nové tabulky `shipping_method_price` (způsob dopravy, měna, částka). | Dnes je cena jedna hodnota v jedné měně. |
| `shipping_method`, `payment_method` | Přidat překladové tabulky (`shipping_method_translation`, `payment_method_translation`). | Dnes mají jen jedno `name`. |
| Měny | Nová tabulka `currency` (kód, počet desetinných míst, symbol, aktivní, výchozí). Ostatní tabulky na ni odkazují kódem, bez cizího klíče (hranice modulů). | Jeden zdroj pravdy a validace povolených měn. |
| Jazyky | Nová tabulka `language` (kód, název, aktivní, výchozí) se stejným principem. | Stejný důvod. |
| `customer` | Sloupec `locale` (preferovaný jazyk, např. pro e-maily). | Komunikace v jazyce zákazníka. |
| Jednotky | Nové tabulky `unit` a `unit_translation`. Sloupec `attribute.unit` (text) nahrazen odkazem `unit_id`. Provedeno v `001`. | Názvy a symboly jednotek se překládají a spravují v administraci. |
| Systémové texty | Nová tabulka `translation_message` (modul Core). Provedeno v `001`. | Překlady rozhraní v databázi, rozdělené doménami. |
| Demo data | Každý překladový řádek ve dvou jazycích, každá varianta s cenou v obou měnách. | Ukázka správného fungování. |

Už existující a beze změny: překladové tabulky katalogu, `variant_price` a `option_value_price` s měnou u každé ceny, `locale` a `currency` v košíku a objednávce, unikátní `slug` podle jazyka.

## 9. Co nesmí chybět kromě zajímavých funkcí

Seznam "nudných" věcí, na kterých obchodník skutečně stojí. Zatím jen sepsáno, nepřiřazeno k vlnám. **[OTEVŘENO]**

- Import a export produktů.
- Vratky a reklamace.
- Napojení na srovnávače (Heureka, Zboží.cz).
- SEO nástroje.
- Více jazyků a měn.
- Uživatelské role v administraci.
- České specifika: Zásilkovna, GoPay/Comgate, export do Pohody nebo ISDOC, správné DPH včetně OSS.

## 10. Výuková stránka

- Každý demo produkt je zároveň **lekce**: zadání, rozhodnutí (ADR), kde v kódu to najít a jaké testy to ověřují. **[NAVRŽENO]**
- Architektonická pravidla jsou **vynucena automaticky**, ne jen zdokumentována. **[NAVRŽENO]**

## 11. Otevřené otázky

1. Text licence a podmínek služby, GDPR smlouva o zpracování (právník).
2. Nástroj pro hromadnou správu mnoha instancí (migrace, zálohy, aktualizace).
3. Vyhledávání: potvrdit MySQL `FULLTEXT` + volitelný Elasticsearch/OpenSearch.
4. Vývojové prostředí pro ne-Windows (Docker, DDEV).
5. Cena v databázi: s DPH, nebo bez (sloupec `tax_included`, potvrdit výchozí).
6. Rozsah a pořadí "nudných" funkcí (kapitola 9).
7. Rozsah prvního milníku: obchod nasaditelný pro reálného zákazníka.
8. **Adresy podle jazyka:** jazyk v URL (`/cs/...`, `/en/...`), nebo jiný způsob? Má výchozí jazyk prefix?
9. **Kde jsou výchozí jazyk a měna:** v konfiguraci instance (NEON), nebo v databázi (tabulka `setting`, měnitelné z administrace)?
10. **DPH v demo datech:** jen české sazby, nebo už i ukázka jiné země?
11. **Zaokrouhlování částek** (např. u hotovosti) a případný nástroj na návrh cen v EUR podle kurzu.
12. **Překlady:** kdo smí editovat které domény (role v administraci) a zda se vede historie změn textů.

## 12. Doporučené další kroky

1. Zrevidovat a spustit schéma vlny 1, připravit seed data pro demo produkty 1 až 3.
2. Založit repozitář (soukromý), strukturu modulů a nástroje: PHPStan level max, coding standard, CI.
3. Napsat první ADR (modulární monolit, instance na obchod, Explorer a `ActiveRow` jen v repozitářích).
4. Rozhodnout licenci a podmínky služby s právníkem.
