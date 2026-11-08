# ArgoMi BestShop – záměr a rozhodnutí

**Stav:** aktuální kanonický dokument  
**Revize:** 1.0 · 8. 11. 2026  
**Navazuje na:** [STRUKTURA](STRUKTURA.md), [DATABASE](DATABASE.md), [TRANSLATIONS](TRANSLATIONS.md), [ROADMAP](ROADMAP.md)

Tento dokument zachycuje záměr projektu a rozhodnutí autora. Nezaměňuje budoucí cíle za hotovou implementaci.

## 1. Vize a priority

ArgoMi BestShop je modulární e-shop postavený na PHP a Nette. Cílem je nabídnout ucelený systém s rozsáhlou sadou funkcí v jádře, nikoliv skládat základní funkčnost z povinných pluginů třetích stran.

Priority v pořadí:
1. bezpečnost a ochrana dat,
2. čitelná, udržitelná a typově kontrolovaná architektura,
3. úplná a konzistentní funkčnost jádra,
4. výuková hodnota a srozumitelná dokumentace,
5. možnost provozovat samostatné instalace obchodů.

Projekt je modulární monolit. Moduly jsou hranice odpovědností uvnitř jedné aplikace, nikoliv samostatně instalované balíčky.

## 2. Distribuce, repozitář a práva

- GitHub repozitář je **veřejný**.
- Veřejná dostupnost zdrojového kódu sama o sobě neposkytuje právo k jeho používání, kopírování, úpravám ani šíření.
- Projekt není open source a nesmí být označován jako open source.
- Výchozí autorskoprávní stav: **všechna práva vyhrazena**.
- Konkrétní studentská licence, podmínky služby, případná licence pro zákazníky a právní texty zůstávají otevřeným úkolem. Před distribucí nebo komerčním spuštěním je nutná právní kontrola.
- V repozitáři nesmí zůstat zavádějící metadata MIT, BSD, GPL nebo ISC. Metadata balíčků musí odpovídat proprietárnímu režimu; právní dokumenty musí určit skutečné podmínky.
- Příspěvky třetích stran a oprávnění k jejich začlenění musí být právně ošetřeny před jejich přijetím.

## 3. Provozní model a architektura

- Jedna instalace představuje jeden obchod.
- Každý obchod má vlastní instanci aplikace a vlastní databázi.
- V první architektuře se nepoužívá `shop_id` ani multistore model.
- Aplikace je modulární monolit; veřejná rozhraní modulů a pravidla závislostí jsou popsána v [STRUKTURA](STRUKTURA.md).
- Rozšiřitelnost se řeší přes interní rozhraní, adaptéry, nastavení a doménové události, ne načítáním libovolného cizího kódu do jádra.
- Funkce určené pro všechny obchody patří do jádra. Externí poskytovatelé plateb, dopravy nebo vyhledávání se připojují přes adaptéry.

## 4. Technologie a vývojová pravidla

| Oblast | Rozhodnutí |
|---|---|
| PHP | PHP 8.2+ |
| Framework | Nette 3.2 |
| Šablony | Latte 3 |
| Databáze | MySQL 8.x, InnoDB, utf8mb4 |
| Přístup k databázi | Nette Database Explorer |
| Migrace | Nextras Migrations, čisté SQL |
| Statická analýza | PHPStan level max, bez baseline |
| Coding standard | PER Coding Style 3.0 (PER-CS 3.0), nástupce PSR-12 |
| Testy | Nette Tester; unit, integrační a architektonické testy podle potřeby |
| Front-end CSS | Sass |
| Bundling | esbuild |
| Front-end framework | Bootstrap 5.3.8 + Bootswatch Cosmo 5.3.8 |
| AJAX | Naja 3 |
| Vite | Nepoužívat |
| Dependency Injection | Striktní konstruktorová injekce |
| Debug | Tracy ve vývojovém prostředí |

Front-end assety se sestavují vlastními skripty Sass a esbuild. Front a Admin mají oddělené vstupy a výstupy. Nesmí se do projektu vrátit Vite jen proto, že jej obsahuje výchozí Nette skeleton.

Pravidla:
- `declare(strict_types=1);` v PHP souborech.
- PHP kód dodržuje **PER Coding Style 3.0 (PER-CS 3.0)**; kontrolu a automatické formátování zajišťuje projektový coding-standard nástroj.
- Žádný service locator, žádné statické získávání služeb.
- Databázové dotazy patří do repozitářů; presentery nepracují přímo s databází.
- `ActiveRow` neopouští infrastrukturní/persistence hranici modulu; veřejně se předávají typovaná DTO nebo value objecty.
- Žádné `noescape` v Latte.
- Žádné skládání SQL z nedůvěryhodných řetězců.
- Závislosti se přijímají pouze po ověření údržby, kompatibility, licence a přínosu.

## 5. Databázové principy

- Interní primární klíče jsou celočíselné; veřejné identifikátory se používají tam, kde je to potřeba.
- Peníze se ukládají jako celočíselné minor units spolu s měnou. Nikdy `FLOAT`.
- Množství se ukládá desetinným typem, nikoliv floatem.
- Čas se ukládá v UTC.
- Atributy, varianty a filtrovatelné vlastnosti jsou relační a typované; JSON není náhradou za relační model.
- Objednávka uchovává snímek údajů a cen z okamžiku nákupu.
- Historie cen se ukládá samostatně; zákonný výpočet nejnižší ceny za období je samostatná aplikační odpovědnost.
- Schéma v1 je jeden počáteční celek. Další změny budou přidávány inkrementálně pomocí migrací.

Přesný stav tabulek a migrací je v [DATABASE](DATABASE.md).

## 6. Jazyky, měny a demo data

- Výchozí jazyk: čeština (`cs`).
- Další připravený jazyk: angličtina (`en`).
- Výchozí měna: CZK.
- Další připravená měna: EUR.
- Ceny pro CZK a EUR jsou explicitní ceny; běžný nákup se nesmí spoléhat na automatický kurzový přepočet.
- Košík a objednávka používají jednu zvolenou měnu; měna objednávky se po vytvoření nemění.
- Demo data jsou součástí projektu a zůstávají i v produkční instalaci jako demonstrační obsah.
- Produkční provoz musí bezpečně oddělit demo zákazníky/objednávky od reálných operací. Demo záznam nesmí spustit skutečnou platbu, expedici ani odeslání zákaznického e-mailu.

## 7. Překlady

Překladový model se řídí dokumentem [TRANSLATIONS](TRANSLATIONS.md).

Závazná rozhodnutí:
- Překlady budou rozdělené podle domén/modulů; nebude se dlouhodobě používat jediná obří tabulka pro všechny systémové texty.
- Obsahové překlady entit zůstávají u příslušné domény (např. produktové překlady u katalogu).
- Systémové texty dodávané aplikací jsou chráněné. Administrátor je nesmí přímo přepisovat.
- Administrátor smí spravovat vlastní překlady a vlastní obsahové texty v rozsahu svých oprávnění.
- Společný modul Translation poskytuje infrastrukturu, import, načítání, fallback, cache a kontrolu konzistence; nevlastní obsahové překlady všech modulů.
- Přechod ze současné tabulky `translation_message` musí proběhnout migrací bez ztráty dat.

## 8. Bezpečnost a identity

Bezpečnost se zavádí postupně podle ROADMAP; dokumentace nesmí tvrdit, že neimplementované prvky již fungují.

Cílové zásady:
- CSP s nonce a bez `unsafe-inline`, kde to architektura umožňuje.
- CSRF ochrana formulářů a citlivých akcí.
- Kontextové escapování výstupu, validace vstupů.
- HTML obsah čistit přes HTMLPurifier nebo schválený ekvivalent.
- Hesla ukládat výhradně bezpečným hashováním.
- Omezení pokusů o přihlášení, bezpečné session a ochrana obnovy hesla.
- Oddělené zákaznické a administrátorské identity a oprávnění.
- RBAC a audit citlivých administrátorských operací.
- 2FA pro administrátory.
- OAuth 2.0 jako budoucí rozšíření, nikoliv předpoklad první implementace.
- Platební údaje karet neukládat; používat tokenizaci poskytovatele.
- Nahrané soubory ukládat mimo webroot a kontrolovat na serveru.
- Logy nesmí bezdůvodně obsahovat hesla, tokeny ani citlivé osobní údaje.

## 9. Provoz, zálohy a obnova

Provozní připravenost je součást ROADMAP. Cílem je:
- zálohovat databázi i soubory,
- uchovávat zálohy odděleně od produkčního serveru,
- definovat retenci a odpovědnost,
- před rizikovou migrací pořídit ověřitelnou zálohu,
- pravidelně zkoušet obnovu,
- mít postup pro rollback aplikace a obnovu dat,
- monitorovat dostupnost, chyby, kapacitu a běh úloh.

Konkrétní RPO/RTO a retence zůstávají otevřenými provozními parametry.

## 10. Demo produkty

Původní sada 15 zkušebních scénářů zůstává dlouhodobým cílem, rozděleným do čtyř vln:
- vlna 1: základní varianty a katalog,
- vlna 2: sady, množstevní slevy, předobjednávky a desetinná množství,
- vlna 3: plošné zboží, digitální licence, věková omezení, vratné zálohy a poukazy,
- vlna 4: konfigurátor, personalizace/upload a předplatné.

Aktuální seed SQL obsahuje pouze počáteční část této sady. Přesný obsah je popsán v DATABASE a další naplnění je úkolem ROADMAP.

## 11. Rozhodnutí, která zůstávají otevřená

Otevřené body se vedou v ROADMAP a nesmí se svévolně považovat za schválené:
- právní licence a podmínky hostované služby,
- GDPR smluvní dokumentace a retence,
- výchozí daňový režim cen,
- jazykové URL,
- B2B rozsah první verze,
- konkrétní platební a dopravní poskytovatelé,
- volba Docker/DDEV mimo současné Windows prostředí,
- PDF, fronty, monitoring a SLA,
- přesné RPO/RTO a retenční lhůty.

## 12. Změnová historie

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Původní dokument ZÁMĚR v0.1/v0.2. |
| 8. 11. 2026 | v1.0: sjednocení s potvrzenou strukturou aplikace, veřejným repozitářem s vyhrazenými právy, doménovým přístupem k překladům a skutečným stavem databáze. |
| 8. 11. 2026 | Rozhodnutí: PHP kód se řídí PER Coding Style 3.0 (PER-CS 3.0). |
