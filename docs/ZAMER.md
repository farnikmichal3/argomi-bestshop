# ArgoMi-BestShop – záměr a rozhodnutí

**Stav:** aktuální kanonický dokument  
**Revize:** 1.33 · 13. 11. 2026
**Navazuje na:** [STRUKTURA](STRUKTURA.md), [DATABASE](DATABASE.md), [TRANSLATIONS](TRANSLATIONS.md), [ROADMAP](ROADMAP.md)

Tento dokument zachycuje záměr projektu a rozhodnutí autora. Nezaměňuje budoucí cíle za hotovou implementaci.

## 1. Vize a priority

ArgoMi-BestShop je modulární e-shop postavený na PHP a Nette. Cílem je nabídnout ucelený systém s rozsáhlou sadou funkcí v jádře, nikoliv skládat základní funkčnost z povinných pluginů třetích stran.

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
- Výchozí autorskoprávní stav: **všechna práva vyhrazena**. Připraví se návrh studentské licence a podmínek služby; jejich konečné znění, stejně jako případná licence pro zákazníky a související právní texty, musí před distribucí nebo komerčním spuštěním zkontrolovat právník.
- V repozitáři nesmí zůstat zavádějící metadata MIT, BSD, GPL nebo ISC. Metadata balíčků musí odpovídat proprietárnímu režimu; právní dokumenty musí určit skutečné podmínky.
- Příspěvky třetích stran a oprávnění k jejich začlenění musí být právně ošetřeny před jejich přijetím.

## 3. Provozní model a architektura

- Jedna instalace představuje jeden obchod.
- Každý obchod má vlastní instanci aplikace a vlastní databázi.
- V první architektuře se nepoužívá `shop_id` ani multistore model.
- Aplikace je modulární monolit; veřejná rozhraní modulů a pravidla závislostí jsou popsána v [STRUKTURA](STRUKTURA.md).
- Rozšiřitelnost se řeší přes interní rozhraní, adaptéry, nastavení a doménové události, ne načítáním libovolného cizího kódu do jádra.
- Funkce určené pro všechny obchody patří do jádra. Externí poskytovatelé plateb, dopravy nebo vyhledávání se připojují přes adaptéry.
- Platební integrace bude používat vlastní rozhraní `PaymentProvider`; konkrétní platební brána se vybere až před produkčním nasazením podle cílového trhu, podporovaných metod, poplatků a podmínek. Objednávkový proces nebude závislý na konkrétním poskytovateli.
- Doručovací integrace bude používat vlastní rozhraní `ShippingProvider` a samostatné adaptéry jednotlivých dopravců. Architektura umožní používat více dopravců současně; konkrétní integrace se vyberou podle cílového trhu a potřeb obchodu.
- Vyhledávání produktů má vlastní rozhraní `ProductSearch`; výchozí implementací je MySQL FULLTEXT. Elasticsearch nebo OpenSearch lze později připojit jako volitelnou implementaci přes stejné rozhraní.

## 4. Technologie a vývojová pravidla

| Oblast | Rozhodnutí |
|---|---|
| PHP | PHP 8.3+ |
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
| Administrační datagrid | Ublaboo Datagrid; vlastní aplikační obal pro omezení přímé závislosti na knihovně |
| Front-end framework | Bootstrap 5.3.8 + Bootswatch Cosmo 5.3.8 |
| AJAX | Naja 3 |
| Vite | Nepoužívat |
| Dependency Injection | Striktní konstruktorová injekce |
| Debug | Tracy ve vývojovém prostředí |

Pro administrační tabulky je zvolen Ublaboo Datagrid. Vlastní aplikační vrstva má omezit přímou závislost na knihovně a umožnit její případnou budoucí náhradu. Knihovna zatím není v projektu implementována ani nakonfigurována; jde o schválenou technologickou volbu pro budoucí tvorbu administrace.

Front-end assety se sestavují vlastními skripty Sass a esbuild. Front a Admin mají oddělené vstupy a výstupy. Nesmí se do projektu vrátit Vite jen proto, že jej obsahuje výchozí Nette skeleton.

Vývojovým prostředím projektu je Laragon na Windows. Docker ani DDEV se zavádět nebudou. Přenositelnost na další počítač zajistí zdokumentované a verzované požadavky na nástroje a jejich verze, instalační postup pro Laragon a reprodukovatelný postup zkopírování projektu. Cílem je po instalaci odpovídajících verzí Laragonu a nástrojů pokračovat s kopií projektu bez změny architektury.

Pravidla:
- `declare(strict_types=1);` v PHP souborech.
- PHP kód dodržuje **PER Coding Style 3.0 (PER-CS 3.0)**; kontrolu a automatické formátování zajišťuje projektový coding-standard nástroj.
- Žádný service locator, žádné statické získávání služeb.
- Databázové dotazy patří do repozitářů; presentery nepracují přímo s databází.
- `ActiveRow` neopouští infrastrukturní/persistence hranici modulu; veřejně se předávají typovaná DTO nebo value objecty.
- Žádné `noescape` v Latte.
- Žádné skládání SQL z nedůvěryhodných řetězců.
- Závislosti se přijímají pouze po ověření údržby, kompatibility, licence a přínosu.


### Verzování a changelog

- Vydání platformy se verzují podle **Semantic Versioning (SemVer)** ve formátu `MAJOR.MINOR.PATCH`: MAJOR označuje nekompatibilní změny, MINOR zpětně kompatibilní nové funkce a PATCH opravy chyb a bezpečnostní opravy.
- Každé produkční vydání dostane neměnný Git tag ve formátu `vMAJOR.MINOR.PATCH` (např. `v1.4.2`).
- Repozitář bude obsahovat `CHANGELOG.md` s přehledem nových funkcí, změn, oprav a bezpečnostních oprav.
- Provozovatelé obchodů dostanou srozumitelný přehled změn, které se jich týkají; interní technické změny není nutné prezentovat jako uživatelské novinky.
- Verze označuje vydání platformy, nikoliv samostatnou verzi každého obchodu. Jednotlivé instalace mohou aktualizace nasazovat v různém čase.
- Databázové změny jsou součástí vydání a provádějí se dopřednými migracemi. Již provedené historické migrace se nepřepisují; před rizikovou migrací se pořizuje záloha podle provozních zásad.


### Generování faktur a PDF

- Pro generování PDF dokumentů se používá knihovna **mPDF** v rámci PHP aplikace; nepřidává se samostatná Python služba ani externí proces WeasyPrint.
- Faktury a další právně významné doklady se generují ze spravovaných šablon a po vystavení se uchovávají jako neměnný dokument. Opravy a změny se řeší novým opravným nebo navazujícím dokladem, nikoliv přepsáním již vystaveného PDF.
- Výstupní PDF musí být ověřen bezpečnostně i vizuálně; obsah dokladu vychází z neměnného snímku objednávky a údajů platných při vystavení.

### Fronty a plánované úlohy

- Úlohy se ukládají do databázové fronty a zpracovávají samostatným workerem.
- Spouštění workeru a plánovaných úloh zajišťuje systémový **cron**; cron tedy slouží jako spouštěč, nikoliv jako náhrada databázové fronty.
- Návrh umožní pozdější provoz samostatných nebo více souběžných workerů bez změny aplikačního rozhraní pro zadávání úloh.

### Šablony e-mailů

- E-mailové šablony se budou ukládat jako **soubory Latte** v repozitáři, nikoliv do databáze.
- Šablony jsou verzované společně s aplikací a jejich změny procházejí běžnou kontrolou kódu.
- Administrátor nebude v první verzi upravovat zdrojové Latte šablony přímo v administraci; případná správa textového obsahu e-mailů se řeší odděleně od šablonovací logiky.

### Generování a velikosti miniatur obrázků

- Originální nahrané obrázky se vždy zachovávají beze změny; miniatury a odvozené varianty se generují z originálu.
- Výchozím formátem generovaných obrazových variant je **WebP**. **AVIF** se může generovat jako další optimalizovaná varianta, pokud je jeho generování dostupné a provozně přiměřené.
- Při zobrazování se upřednostní AVIF, následně WebP; originál slouží jako fallback tam, kde odvozené formáty nejsou vhodné nebo dostupné.
- Systém používá centrálně definované velikosti/varianty podle účelu zobrazení. Jejich rozměry a kvalita se řídí konfigurací, nikoliv hodnotami rozptýlenými v jednotlivých modulech.
- Generování respektuje poměr stran a podporuje definovaný způsob ořezu; front-end využívá responzivní varianty obrázků podle velikosti zobrazovací plochy.

## 5. Databázové principy

- Interní primární klíče jsou celočíselné; veřejné identifikátory se používají tam, kde je to potřeba.
- Peníze se ukládají jako celočíselné minor units spolu s měnou. Nikdy `FLOAT`.
- Základní ceny produktů a cenové podklady se ukládají bez DPH. DPH se vypočítává z příslušné daňové třídy a platných daňových pravidel; způsob zobrazování cen s DPH nebo bez DPH řídí nastavení obchodu `tax_included`. Objednávka uchovává neměnný snímek použitých cen, sazeb a vypočtených částek.
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
- První verze e-shopu je zaměřena na B2C. Datový a doménový model ale od začátku počítá s firemními zákazníky a údaji IČO/DIČ; ověřování v ARES (ČR) a VIES (EU), B2B ceníky a individuální firemní podmínky se doplní později.
- Všechny jazykové varianty URL mají vždy explicitní jazykový prefix, včetně výchozího jazyka (např. `/cs/produkty`, `/en/products`).
- Výchozí měna: CZK.
- Další připravená měna: EUR.
- Ceny pro CZK a EUR jsou explicitní ceny; běžný nákup se nesmí spoléhat na automatický kurzový přepočet.
- Košík a objednávka používají jednu zvolenou měnu; měna objednávky se po vytvoření nemění.
- Doménové rozhraní `CurrencyProvider` poskytuje výchozí a aktivní měny. Výchozí měna musí být nastavena a aktivní; pokud chybí, poskytovatel vyhodí `DefaultCurrencyNotFoundException` místo tichého použití náhradní měny.
- Aktivní měny se načítají z tabulky `currency` a řadí podle `sort_order`, při shodě podle kódu. Doménový objekt `Currency` obsahuje pouze kód a počet desetinných míst; název a symbol zůstávají mimo peněžní výpočty.
- Demo data jsou součástí projektu a zůstávají i v produkční instalaci jako demonstrační obsah.
- Produkční provoz musí bezpečně oddělit demo zákazníky/objednávky od reálných operací. Demo záznam nesmí spustit skutečnou platbu, expedici ani odeslání zákaznického e-mailu.

## 7. Překlady

Překladový model se řídí dokumentem [TRANSLATIONS](TRANSLATIONS.md).

Závazná rozhodnutí:
- Překlady budou rozdělené podle domén/modulů; nebude se dlouhodobě používat jediná obří tabulka pro všechny systémové texty.
- **Veškerý překládaný text je za běhu aplikace uložen výhradně v databázi.** Platí to pro obsahové překlady, systémové texty dodané aplikací i vlastní texty administrátora/obchodníka. SQL databázové migrace a seed skripty smějí obsahovat výchozí systémové překladové hodnoty pro vložení nebo aktualizaci databáze. Nesmějí však existovat runtime souborové katalogy ani fallback ze souborů; aplikace načítá překlady výhradně z databáze. Kód obsahuje pouze stabilní identifikátory/klíče a technickou logiku.
- Obsahové překlady entit zůstávají u příslušné domény (např. produktové překlady u katalogu).
- Systémové texty dodávané aplikací jsou chráněné. Administrátor je nesmí přímo přepisovat.
- Administrátor smí spravovat vlastní překlady a vlastní obsahové texty v rozsahu svých oprávnění.
- Společný modul Translation poskytuje databázovou infrastrukturu, správu systémových klíčů, import/aktualizaci databázových záznamů, fallback, cache a kontrolu konzistence; nevlastní obsahové překlady všech modulů. Výchozí systémové hodnoty se do databáze dostávají řízenou databázovou migrací nebo seedem; SQL soubory mohou obsahovat jejich výchozí znění, ale nikdy se z nich nenačítá překlad za běhu aplikace.
- Přechod ze současné tabulky `translation_message` musí proběhnout migrací bez ztráty dat.

## 8. Bezpečnost a identity

Bezpečnost se zavádí postupně podle ROADMAP; dokumentace nesmí tvrdit, že neimplementované prvky již fungují.

Cílové zásady:
- CSP s nonce a bez `unsafe-inline`, kde to architektura umožňuje.
- CSRF ochrana formulářů a citlivých akcí.
- Kontextové escapování výstupu, validace vstupů.
- Textový obsah ukládat jako čistý Markdown, nikoliv jako HTML. Markdown se při vykreslení převede na HTML a výsledné HTML se bezpečně vyčistí přes HTMLPurifier nebo schválený ekvivalent; libovolné HTML vložené autorem se nepovažuje za důvěryhodné.
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

**Závazný katalog tvoří 15 demo produktů rozdělených do čtyř vln.** Tento seznam je kanonický a při další práci se již znovu nehledá ani nevymýšlí. Slouží k ověření širokého spektra typů produktů a obchodních scénářů. Jeden produkt může záměrně demonstrovat více souvisejících funkcí.

### Vlna 1 – základy katalogu a variant

| # | Produkt | Ověřované scénáře |
|---:|---|---|
| 1 | Bavlněné tričko | Varianty velikost × barva, SKU, EAN, sklad a hash kombinace |
| 2 | GaN nabíječka | Varianty podle výkonu, počtu portů a barvy |
| 3 | USB-C kabel | Varianty délky, konektorů a barvy |
| 4 | Kniha / výběrová káva | Jednoduchý produkt, výchozí varianta, pevná cena a sklad |
| 5 | Hrnek s vlastním potiskem | Zákaznické volby, vlastní text, příplatky a dynamická cena |

### Vlna 2 – ceny, sklad a množství

| # | Produkt | Ověřované scénáře |
|---:|---|---|
| 6 | Dárková sada | Bundle, dostupnost podle skladových zásob komponent a odpis komponent |
| 7 | Kancelářský papír / balení šroubů | Množstevní cenové hladiny a přepočet jednotkové ceny |
| 8 | Limitovaná edice tenisek | Předobjednávky a očekávané datum naskladnění/doručení |
| 9 | Látka / kabel na metráž | Desetinná množství, prodejní krok a přesné výpočty bez floatů |
| 10 | Koupelnová dlažba / podlaha | Cena za m², prodej na celá balení, výpočet plochy a zaokrouhlení |

### Vlna 3 – speciální druhy zboží

| # | Produkt | Ověřované scénáře |
|---:|---|---|
| 11 | Elektronická licence / e-kniha | Digitální produkt, licenční klíč a doručení bez fyzické dopravy |
| 12 | Řemeslné pivo ve vratné lahvi | Věkové omezení, ověření při předání a vratná záloha za obal |

### Vlna 4 – pokročilé funkce

| # | Produkt | Ověřované scénáře |
|---:|---|---|
| 13 | Dárkový poukaz | Unikátní kód, platnost, částečné čerpání a použití jako platební metoda |
| 14 | Konfigurovatelná fotokniha | Konfigurace parametrů, upload PDF/TIFF, validace a zabezpečené uložení souboru |
| 15 | Kávové předplatné | Pravidelné odběry, tokenizace platební karty a automatické platby |

Produkt č. 12 záměrně spojuje věkové omezení a vratnou zálohu. Produkt č. 14 spojuje konfigurátor a upload zákaznického souboru. Tím katalog pokrývá všech 15 původně navržených funkčních scénářů v rámci 15 produktů.

**První tři produkty jsou již součástí aktuálního demo seed SQL:** tričko, USB-C kabel a GaN nabíječka. Kanonické pořadí katalogu je tričko, GaN nabíječka, USB-C kabel; pořadí a identifikátory existujících záznamů v SQL se tímto dokumentačním rozhodnutím nemění. Jejich případné sjednocení v seed datech se provede samostatně a kontrolovanou změnou.

Tento seznam určuje cílový obsah demo katalogu, nikoliv tvrzení, že všechny uvedené funkce již databáze nebo aplikace implementuje. Před realizací každého scénáře se ověří aktuální SQL a existující aplikační řešení; doplní se pouze skutečně chybějící části.

## 11. Otevřené body před produkčním nasazením

Následující body nejsou nevyřešenými architektonickými rozhodnutími. Jde o právní, provozní nebo implementační detaily, které musí být dopracovány před odpovídajícím nasazením:

- Připravit a právně zkontrolovat studentskou licenci, podmínky služby a případné licenční podmínky pro zákazníky.
- Připravit DPA a GDPR dokumentaci pro hostované obchody; finální znění ověří právník a specialista na ochranu osobních údajů.
- Při implementaci nastavení obchodu určit výchozí hodnotu `tax_included`; cenový model již stanoví ukládání základních cen bez DPH.
- Konkrétní platební brány a dopravce vybrat podle cílového trhu před produkčním nasazením.
- Docker ani DDEV nezavádět; Laragon na Windows zůstává jediným podporovaným vývojovým prostředím projektu. Přenositelnost zajistit přesným popisem verzí a postupem přípravy dalšího počítače.
- Před hostovaným provozem určit konkrétní monitoring, reakční doby incidentů a provozní parametry SLA; cílová dostupnost 99,5 % měsíčně je již schválena.
- Pro konkrétní hosting stanovit cíle RPO/RTO.
- Stanovit přesné retenční lhůty jednotlivých kategorií dat podle právních povinností a provozních potřeb.

## 12. Exportní produktové feedy

Export produktů bude řešen společným interním rozhraním a samostatnými adaptéry pro jednotlivé externí platformy. První cílové platformy jsou **Heureka, Zboží.cz a Google Shopping**.

- Interní katalog a doménový model zůstávají nezávislé na formátech a požadavcích jednotlivých platforem.
- Společná exportní vrstva poskytne adaptérům normalizovaná produktová data.
- Každý adaptér odpovídá za mapování polí, kategorií, dostupnosti, cen a dalších požadavků konkrétní platformy.
- Přidání další platformy nesmí vyžadovat změnu základního katalogového modelu, pokud nejde o skutečně chybějící obecnou produktovou informaci.
- Konkrétní formáty, povinná pole, plánování exportů, aktualizace a chybové stavy se specifikují při realizaci exportních feedů.

## 13. Import produktů

Import produktů bude řešen společným interním rozhraním a samostatnými adaptéry pro vstupní formáty **CSV a XML**.

- Importní vrstva převede vstupní data na normalizovaný interní formát nezávislý na konkrétním zdroji.
- Adaptéry odpovídají za načtení a mapování sloupců či polí ze zdrojového formátu.
- Před zápisem do katalogu proběhne validace a kontrola konzistence dat.
- Uživatel dostane náhled připravovaných změn a možnost import zkontrolovat před potvrzením.
- Výsledek importu bude obsahovat protokol úspěšně zpracovaných i chybových záznamů, včetně důvodu chyby.
- Konkrétní pravidla párování existujících produktů, aktualizací, duplicit a řešení konfliktů budou upřesněna při návrhu implementace.

## 14. SLA a dostupnost hostované služby

Pro budoucí hostovanou službu stanovujeme výchozí cílovou dostupnost **99,5 % za kalendářní měsíc**.

- Plánovaná údržba bude předem oznámena a evidována samostatně.
- Dostupnost, chyby a výkon služby budou průběžně monitorovány.
- Budou definovány reakční doby pro jednotlivé úrovně incidentů.
- V této fázi se nezavádějí finanční kompenzace za nedodržení SLA; případné kompenzace se posoudí podle zkušeností z reálného provozu.
- Toto SLA se vztahuje na budoucí hostovanou službu, nikoli na lokální instalace.

## 15. Zálohování a obnova po havárii

Pro budoucí hostovanou službu stanovujeme tyto základní zásady zálohování a obnovy:

- Provádět denní plné zálohy databáze.
- Průběžně zálohovat databázové změny pomocí binárních logů (binlogů), aby byla možná obnova k určitému okamžiku.
- Ukládat zálohy mimo hlavní provozní server.
- Zálohy chránit šifrováním při přenosu i při uložení.
- Pravidelně prakticky testovat obnovu ze záloh.
- Konkrétní cíle RPO (maximální přípustná ztráta dat) a RTO (cílová doba obnovy služby) stanovit podle požadavků a možností konkrétního provozního prostředí.

## 16. Historie rozhodnutí

| Datum | Změna |
|---|---|
| 4. 11. 2026 | Původní dokument ZÁMĚR v0.1/v0.2. |
| 8. 11. 2026 | v1.0: sjednocení s potvrzenou strukturou aplikace, veřejným repozitářem s vyhrazenými právy, doménovým přístupem k překladům a skutečným stavem databáze. |
| 8. 11. 2026 | Rozhodnutí: PHP kód se řídí PER Coding Style 3.0 (PER-CS 3.0). |
| 8. 11. 2026 | F0-16: Zachováváme režim „všechna práva vyhrazena“. Připravíme návrh studentské licence a podmínek služby; finální znění před distribucí nebo komerčním spuštěním zkontroluje právník. |
| 8. 11. 2026 | F0-17: Připravíme návrh smlouvy o zpracování osobních údajů (DPA) a GDPR dokumentace pro hostované obchody; finální znění a související retenční pravidla před spuštěním zkontroluje právník a specialista na ochranu osobních údajů. |
| 8. 11. 2026 | F0-18: MySQL FULLTEXT jako výchozí vyhledávání; Elasticsearch/OpenSearch jako možné pozdější implementace přes `ProductSearch`. |
| 8. 11. 2026 | F0-19: Laragon na Windows zůstává hlavním vývojovým prostředím; původní možnost budoucího Dockeru/DDEV byla následně zamítnuta. |
| 10. 11. 2026 | Konečné rozhodnutí k F1-35: zůstáváme výhradně u Laragonu. Přenositelnost zajistí shodné verze nástrojů a reprodukovatelný postup přípravy dalšího počítače; Docker ani DDEV nebudeme zavádět. |
| 8. 11. 2026 | F0-20: Základní ceny se ukládají bez DPH; DPH se vypočítává podle daňových pravidel a `tax_included` řídí zobrazování cen. |
| 8. 11. 2026 | F0-21: Všechny jazykové varianty URL mají explicitní prefix, včetně výchozího jazyka (`/cs`, `/en`). |
| 8. 11. 2026 | F0-22: První verze je B2C; datový a doménový model počítá s firemními zákazníky a údaji IČO/DIČ, zatímco ověřování ARES/VIES a pokročilé B2B funkce se odkládají. |
| 8. 11. 2026 | F0-23: Platební integrace bude postavena na vlastním rozhraní `PaymentProvider`; konkrétního poskytovatele vybereme až před produkčním nasazením. |
| 8. 11. 2026 | F0-24: Doručovací integrace bude používat `ShippingProvider` a samostatné adaptéry; systém umožní současné použití více dopravců. |
| 8. 11. 2026 | F0-26: Pro administrační tabulky byl zvolen Ublaboo Datagrid. Zatím není implementován ani nakonfigurován; bude použit při tvorbě administrace a zapouzdřen vlastní aplikační vrstvou. |
| 8. 11. 2026 | F0-27: Obsahové texty se ukládají jako čistý Markdown; při vykreslení se převádějí na HTML a výsledné HTML se čistí přes HTMLPurifier nebo schválený ekvivalent. |
| 8. 11. 2026 | F0-28: Platforma používá SemVer, produkční vydání označuje Git tag, změny eviduje `CHANGELOG.md` a obchodníkům poskytuje srozumitelný přehled relevantních změn; verze je společná pro vydání platformy, nikoliv samostatná pro každý obchod. Databázové změny probíhají dopřednými migracemi. |
| 8. 11. 2026 | F0-29: Pro generování PDF se volí mPDF. Doklady se generují ze spravovaných šablon, vycházejí z neměnného snímku objednávky a po vystavení se uchovávají beze změn. |
| 8. 11. 2026 | F0-30: Originály obrázků se zachovávají; miniatury se generují primárně ve WebP, AVIF je doplňková optimalizovaná varianta. Velikosti a kvalita se řídí centrální konfigurací, s podporou ořezu, poměru stran a responzivního zobrazování. |
| 8. 11. 2026 | F0-32: E-mailové šablony se ukládají jako Latte soubory v repozitáři a verzují se společně s aplikací; jejich zdrojový kód se neupravuje v administraci. |
| 8. 11. 2026 | F0-31: Úlohy se ukládají do databázové fronty a zpracovává je worker spouštěný cronem. Architektura umožní pozdější provoz samostatných či více souběžných workerů bez změny aplikačního rozhraní. |
| 9. 11. 2026 | Schválen závazný katalog 15 demo produktů ve čtyřech vlnách; první tři položky navazují na existující demo seed SQL. Dvě položky kombinují související scénáře (věkové omezení + vratná záloha; konfigurátor + upload). |
| 9. 11. 2026 | F0-38: Exportní produktové feedy budou používat společné interní rozhraní a samostatné adaptéry pro Heureku, Zboží.cz a Google Shopping; katalog zůstane nezávislý na platformách. |
| 9. 11. 2026 | F0-39: Import produktů bude používat společné interní rozhraní a adaptéry CSV/XML, validaci, náhled před potvrzením a protokol výsledků včetně chyb. |
| 9. 11. 2026 | F0-40: Pro budoucí hostovanou službu platí výchozí cíl dostupnosti 99,5 % měsíčně, monitoring, předem oznámená údržba a definované reakční doby; bez finančních kompenzací v této fázi. |
| 9. 11. 2026 | F0-41: Zálohování hostované služby bude zahrnovat denní plné zálohy, průběžné binlogy, oddělené a šifrované uložení a pravidelné testy obnovy; RPO/RTO se stanoví pro konkrétní prostředí. |
| 9. 11. 2026 | F0-42: Historie cen bude sloužit k automatickému určení nejnižší ceny za předchozích 30 dnů při slevách; uchování historie a zákonné výjimky budou součástí implementace. |
| 9. 11. 2026 | F0-43: Oficiální název projektu je **ArgoMi-BestShop**, technický název databáze zůstává **`argomi_bestshop`**; existující repozitář a projektová cesta `argomi-bestshop` se nemění. |
| 9. 11. 2026 | F0-45: Projekt nebude podporovat instalovatelné pluginy třetích stran. Funkce budou součástí vlastního jádra a interních modulů; externí služby se napojují přes interní rozhraní a adaptéry. |
| 9. 11. 2026 | F0-46: Tracy pro vývojovou diagnostiku, Monolog pro aplikační logování, strukturované logy s úrovněmi DEBUG až CRITICAL, zákaz zápisu citlivých údajů; Sentry případně později pro produkční hlášení chyb. |
| 9. 11. 2026 | F0-47: Během současného vývoje se pracuje přímo ve větvi `main` s lokální zálohou; po prvním vydání se přejde na stabilní `main`, vývojové větve, Pull Requesty a Git tagy podle SemVer. |
| 9. 11. 2026 | F0-48: Závazným cílem přístupnosti zákaznického rozhraní i administrace je WCAG 2.1 AA. |
| 9. 11. 2026 | F0-49: Stanovena pravidla pro zákonné uchovávání objednávek a dokladů, GDPR odstranění/anonymizaci osobních údajů, retenci logů a ochranu auditních záznamů; přesné lhůty se stanoví podle práva a provozu. |
| 9. 11. 2026 | F0-50: Kontrolují se licence přímých i nepřímých závislostí; upřednostňují se licence slučitelné s komerčním použitím a povinnosti se evidují a kontrolují před vydáním. |
| 9. 11. 2026 | F0-53: Demo data a testovací operace musí být oddělené od skutečného provozu; demo objednávky nesmějí vyvolat reálné platby, expedici ani jiné externí operace. |
| 9. 11. 2026 | F0-54: Nette Database Explorer je jedinou databázovou vrstvou běžného aplikačního kódu; Nextras ORM bude odstraněno, zatímco Nextras Migrations/DBAL mohou zůstat pro migrace a testování. Kontrola PHP a NEON souborů potvrdila jako jedinou zbývající referenci `OrmModel.php`; jeho odstranění je vedeno ve F1-37. |

## 17. Historie cen a pravidla pro slevy (Omnibus)

Produktový cenový model musí uchovávat historii cen tak, aby bylo možné správně určit a doložit referenční cenu při oznámení slevy podle pravidel evropské Omnibus směrnice.

- Při oznámení slevy se jako referenční cena použije zpravidla nejnižší cena, za kterou byl produkt nabízen během předchozích 30 dnů.
- Systém musí umět tuto cenu automaticky vyhodnotit z uložené historie cen.
- Historie musí být uchovávána tak, aby její změny nebo mazání neznemožnily doložení správnosti zobrazené slevy.
- Implementace musí zohlednit zákonné výjimky a zvláštní případy, například postupné zvyšování slevy.
- Konkrétní právní výjimky a jejich přesné vyhodnocení budou ověřeny při implementaci podle aktuálně platných předpisů.




## 18. Název projektu a databáze

Oficiální název projektu je **ArgoMi-BestShop**. Technický název databáze zůstává **`argomi_bestshop`**.

- Název projektu se používá v dokumentaci a uživatelské prezentaci.
- Repozitář, technický identifikátor projektu a existující projektová cesta používají podobu `argomi-bestshop`.
- Existující databáze `argomi_bestshop` se nepřejmenovává.
- Toto rozhodnutí sjednocuje pojmenování; nevyžaduje změnu databáze ani projektové cesty.


## 19. Rozšiřování systému a integrace externích služeb

ArgoMi-BestShop nebude poskytovat systém instalovatelných pluginů třetích stran. Funkce e-shopu budou součástí vlastního jádra a jeho interních modulů.

- Nebude existovat veřejný mechanismus pro instalaci, načítání ani správu pluginů třetích stran.
- Rozšiřování funkcí bude probíhat vývojem a verzováním vlastních modulů v rámci projektu.
- Napojení externích služeb (například platebních bran, dopravců nebo vyhledávacích služeb) bude řešeno interními rozhraními a adaptéry, nikoliv pluginovým systémem.
- Externí poskytovatelé služeb nejsou pluginy a jejich integrace nesmí umožnit obcházení bezpečnostních a doménových pravidel jádra.


## 20. Logování a sledování chyb

ArgoMi-BestShop bude používat jednotný standard logování a diagnostiky napříč moduly.

- **Tracy** bude sloužit pro vývojové prostředí, ladění a diagnostiku.
- **Monolog** bude standardní aplikační vrstvou pro zapisování logů.
- Logy budou strukturované a budou používat úrovně DEBUG, INFO, WARNING, ERROR a CRITICAL.
- Do logů se nesmějí zapisovat hesla, přístupové tokeny, tajné klíče ani jiné citlivé údaje; citlivá data se musí před zápisem odstranit nebo maskovat.
- **Sentry** lze doplnit později pro automatický sběr a hlášení chyb z produkčního prostředí.


## 21. Strategie práce s větvemi v Gitu

Během současné aktivní fáze vývoje bude projekt vyvíjen přímo ve větvi `main`. Samostatné vývojové větve a Pull Requesty nejsou pro tuto fázi povinné.

- Aktuální vývojový režim: jedna pracovní větev `main`.
- Předpokladem tohoto režimu je průběžná lokální záloha projektu a možnost obnovy.
- Po dokončení systému a jeho prvním vydání se přejde na řízenější práci s větvemi: `main` bude stabilní větev a nové funkce či opravy se budou připravovat v samostatných větvích a slučovat přes Pull Request.
- Vydané verze budou označovány Git tagy podle SemVer.
- Přechod na tento režim proběhne jako samostatný krok před navazujícím vývojem po prvním vydání.


## 22. Přístupnost uživatelského rozhraní

Závazným cílem přístupnosti zákaznického rozhraní i administrace je **WCAG 2.1 na úrovni AA**.

Při návrhu a implementaci se bude dbát zejména na ovládání klávesnicí, dostatečný kontrast, popisky formulářových polí, sémantickou strukturu nadpisů, přístupné chybové hlášky a podporu čteček obrazovky. Soulad se bude ověřovat průběžně při vývoji a před vydáním.


## 23. Mazání dat a doby uchovávání

ArgoMi-BestShop bude uplatňovat řízená pravidla pro mazání dat a uchovávání záznamů v souladu s právními povinnostmi a zásadami ochrany osobních údajů.

- Objednávky a účetní doklady se nesmějí odstranit, pokud trvá zákonná povinnost jejich uchovávání.
- Zákaznické účty musí podporovat bezpečné odstranění nebo anonymizaci osobních údajů v souladu s GDPR a souvisejícími právními povinnostmi.
- Aplikační logy budou mít definovanou dobu uchovávání; po jejím uplynutí budou automaticky mazány.
- Bezpečnostní a auditní záznamy budou chráněny před neoprávněnou změnou či odstraněním.
- Konkrétní retenční lhůty jednotlivých kategorií dat budou stanoveny podle právních požadavků a potřeb skutečného provozu.


## 24. Licence knihoven a závislostí

Před zařazením knihovny do projektu a před každým vydáním bude ověřena licence všech přímých i nepřímých PHP a JavaScript závislostí.

- Upřednostňovány budou otevřené licence vhodné pro komerční použití a distribuci, zejména MIT, BSD a Apache 2.0.
- Nebudou přidávány knihovny s licenčními podmínkami, které by mohly nepřijatelně omezit komerční použití nebo distribuci ArgoMi-BestShop.
- Licence a relevantní licenční povinnosti závislostí budou evidovány v projektové dokumentaci.
- Kontrola licencí bude součástí přípravy každého vydání; nejasné nebo nekompatibilní licence musí být vyřešeny před vydáním.


## 25. Oddělení demo dat od skutečného provozu

Demo data a testovací operace musí být jednoznačně rozlišitelné od skutečných zákaznických a provozních dat.

- Demo data budou jasně označena a oddělena od skutečných dat.
- Demo objednávky nesmějí spustit skutečné platby, expedici ani jiné externí provozní operace.
- Testovací platební brány, dopravci a další externí služby smějí být používáni pouze v odpovídajícím testovacím režimu.
- Produkční prostředí musí bezpečně zabránit použití demo dat a testovacích konfigurací; demo režim nesmí být možné omylem aktivovat pro skutečný provoz.
- Konkrétní technické pojistky a testy tohoto oddělení budou součástí implementace a přípravy vydání.


## 26. Databázová vrstva a ORM

**Nette Database Explorer** je jedinou povolenou databázovou vrstvou pro běžný aplikační kód ArgoMi-BestShop. Moduly, repozitáře a aplikační služby budou pro práci s provozními daty používat Nette Database Explorer; Nextras ORM se v aplikační vrstvě používat nebude.

- Nextras ORM není součástí cílové architektury. Kontrola všech aktuálních PHP a NEON souborů potvrdila, že jedinou zbývající referencí je `OrmModel.php`; model i přímá závislost `nextras/orm` budou odstraněny v rámci F1-37.
- Nepoužívaný soubor `app/Shared/Infrastructure/Persistence/OrmModel.php` bude odstraněn společně s ORM.
- Oddělené nástroje pro databázové migrace a testování jsou povoleny. Zejména Nextras Migrations a případně Nextras DBAL mohou zůstat jako vývojové/migrační nástroje; jejich použití neznamená použití ORM v aplikační vrstvě.
- Migrace, testy ani pomocné nástroje nesmějí zavádět druhou ORM vrstvu do běžného aplikačního kódu.



## 27. Doménový model peněz a měn

Peníze se v doméně reprezentují neměnným hodnotovým objektem `Money`. Částka je vždy celočíselná hodnota v nejmenších jednotkách měny (minor units); desetinná aritmetika ani `float` se nepoužívají.

- `Money` obsahuje celočíselnou částku v minor units a hodnotový objekt `Currency`.
- `Currency` obsahuje třípísmenný velký ISO kód měny a počet desetinných míst (minor units).
- Sčítání, odčítání a porovnávání jsou povoleny pouze pro stejnou měnu. Rozdílná měna vyvolá doménovou výjimku; automatický kurzový přepočet se neprovádí.
- Násobení v základní implementaci `Money` přijímá pouze celočíselný faktor. Násobení desetinným množstvím a pravidla zaokrouhlování se doplní až ve spolupráci s hodnotovými objekty `Quantity` a `Percentage`.
- Přetečení celočíselného rozsahu musí být explicitně odmítnuto výjimkou; nesmí dojít k tichému převodu na `float`.
- `CurrencyProvider`, výběr výchozí a aktivní měny a načítání měn z databáze jsou samostatnou odpovědností navazující implementace F2-03.
- Běžný nákup nepřepočítává CZK a EUR automaticky. Košík a objednávka používají jednu zvolenou měnu a měna objednávky se po vytvoření nemění.


## 28. Doménový model množství

Množství se v doméně reprezentuje neměnným hodnotovým objektem `Quantity` s pevnou přesností tří desetinných míst. Interně se ukládá jako celočíselný počet tisícin; desetinná aritmetika typu `float` se nepoužívá.

- Konstruktor přijímá pouze celočíselnou hodnotu v tisícinách. Pro běžné vytváření slouží pojmenované metody `fromDecimal()` a `fromInteger()`.
- `fromDecimal()` přijímá desetinný řetězec s tečkou a nejvýše třemi desetinnými místy. Vstup s vyšší přesností, čárkou, exponentem nebo jiným neplatným formátem se odmítne; žádné automatické zaokrouhlování ani tichá změna hodnoty nejsou povoleny.
- `toDecimalString()` vrací stabilní desetinný zápis vždy se třemi místy a tečkou. Lokalizované formátování patří do prezentační vrstvy.
- Sčítání, odčítání, porovnávání a celočíselné násobení pracují výhradně s interním integerem. Přetečení se odmítne výjimkou; desetinné násobení a dělení nejsou součástí základního API, dokud nebudou stanovena přesná pravidla zaokrouhlování.
- Záporné hodnoty jsou povoleny, protože mohou vyjadřovat například skladový výdej nebo korekci množství.
- `Quantity` neobsahuje měrnou jednotku a samo nepřevádí mezi kusy, kilogramy, litry ani jinými jednotkami. Kompatibilitu a převody jednotek řeší samostatná doménová odpovědnost.
- Přesnost odpovídá databázovým sloupcům množství typu `DECIMAL(12,3)`; rozsah samotného hodnotového objektu je navíc omezen bezpečným celočíselným rozsahem PHP.
