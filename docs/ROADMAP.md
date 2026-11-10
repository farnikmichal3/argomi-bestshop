# ArgoMi-BestShop – plán vývoje (ROADMAP)

Verze dokumentu 2.51 · 10. 11. 2026 · navazuje na `ZAMER.md`, `STRUKTURA.md`, `DATABASE.md` a `TRANSLATIONS.md`

Tento dokument je **živý seznam kroků**, ke kterému se vracíme a na který navazujeme. Po každé pracovní session aktualizujeme stavy.

## 1. Jak s plánem pracovat

### Stavy kroků

| Stav | Význam |
|---|---|
| `[HOTOVO]` | Krok splňuje definici hotovo (viz níže). |
| `[ROZDĚLÁNO]` | Na kroku se právě pracuje, nebo je hotový koncept čekající na kontrolu. Nejvýše 3 najednou. |
| `[K ÚPRAVĚ]` | Dříve hotový krok se musí upravit (změna rozhodnutí, chyba, přejmenování). |
| `[PLÁN]` | Naplánováno, zatím se nezačalo. |
| `[ROZHODNOUT]` | Čeká na rozhodnutí autora. Navazující kroky nelze začít. |
| `[BLOKOVÁNO]` | Nelze pokračovat kvůli externí překážce (uvede se důvod). |
| `[ZRUŠENO]` | Krok se nebude dělat (uvede se důvod, krok se nemaže). |

### Pravidla vedení

1. **ID kroku** (např. `F5-10`) se nikdy nemění ani nepřečísluje. Nový krok dostane další číslo ve fázi, zrušený krok zůstane s označením `[ZRUŠENO]`.
2. **Jeden krok = jeden ověřitelný výsledek.** Když se při práci ukáže, že je krok velký, rozdělíme ho na nové kroky (např. `F5-10a`, `F5-10b`) a původní označíme jako rozdělený.
3. **Commit a pull request** obsahují ID kroku v názvu (např. `F5-10: Repozitář produktů`).
4. **Rozhodnutí** nejdřív do ZAMER (nebo ADR), potom se mění stav kroku.
5. Při změně stavu se mění i souhrn v kapitole 2 (pokud je plán generovaný skriptem, přegeneruje se).

### Definice hotovo pro kódové kroky

1. Kód odpovídá ADR a zásadám projektu (`declare(strict_types=1)`, striktní DI, žádné statické přístupy, dotazy pouze v repozitářích).
2. PHPStan level max bez výjimek a bez baseline.
3. Texty a formáty jsou v databázi v obou jazycích (žádné napevno zapsané řetězce, měny a čísla).
4. Provedena bezpečnostní kontrola: vstup, výstup (striktní escapování HTML), oprávnění.
5. Dokumentace: README modulu, případně ADR.
6. Pro demo produkty jsou k dispozici demo data a ověření.

### Milníky

| Milník | Obsah |
|---|---|
| **M1: první nasazení** | F0 až F12, F14, F15, F17; z F13 jen základ nad MySQL FULLTEXT; demo produkty 1 až 3 |
| **M2** | F18 (vlna 2: sada, množstevní slevy, předobjednávka, neceločíselné množství) |
| **M3** | F19 (speciální scénáře: dlažba, licence, věkové omezení, vratné zálohy a poukaz; produkty 10–13) |
| **M4** | F20 (vlna 4: fotokniha, nahrání souboru a předplatné; produkty 14–15) |
| **M5** | F21 (funkce, na kterých obchodník stojí; průběžně) |
| **Průběžně** | F16 (výuka) navazuje na hotové fáze |

## 2. Přehled stavu

Celkem **549 kroků**: hotovo 99, rozděláno 1, k úpravě 1, rozhodnout 0, blokováno 0, plán 445, zrušeno 1.

| Fáze | Název | Kroků | HOTOVO | ROZDĚLÁNO | K ÚPRAVĚ | ROZHODNOUT | BLOKOVÁNO | PLÁN | ZRUŠENO |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|
| F0 | Rozhodnutí a dokumentace | 54 | 54 | 0 | 0 | 0 | 0 | 0 | 0 |
| F1 | Prostředí a repozitář | 39 | 35 | 1 | 0 | 0 | 0 | 3 | 0 |
| F2 | Sdílené jádro (Shared) | 16 | 0 | 0 | 0 | 0 | 0 | 16 | 0 |
| F3 | Databáze a migrace | 32 | 10 | 0 | 0 | 0 | 0 | 21 | 1 |
| F4 | Překlady v databázi | 29 | 0 | 0 | 0 | 0 | 0 | 29 | 0 |
| F5 | Catalog (vlna 1) | 36 | 0 | 0 | 0 | 0 | 0 | 36 | 0 |
| F6 | Tax, Pricing, Inventory | 27 | 0 | 0 | 0 | 0 | 0 | 27 | 0 |
| F7 | Cart | 16 | 0 | 0 | 0 | 0 | 0 | 16 | 0 |
| F8 | Zákazníci a administrátoři | 21 | 0 | 0 | 0 | 0 | 0 | 21 | 0 |
| F9 | Orders a Checkout | 27 | 0 | 0 | 0 | 0 | 0 | 27 | 0 |
| F10 | Shipping, Payments, Mailing | 24 | 0 | 0 | 0 | 0 | 0 | 24 | 0 |
| F11 | Front UI | 23 | 0 | 0 | 0 | 0 | 0 | 23 | 0 |
| F12 | Admin UI | 27 | 0 | 0 | 0 | 0 | 0 | 27 | 0 |
| F13 | Vyhledávání | 10 | 0 | 0 | 0 | 0 | 0 | 10 | 0 |
| F14 | Bezpečnost a kvalita | 25 | 0 | 0 | 0 | 0 | 0 | 25 | 0 |
| F15 | Provoz a hostování | 18 | 0 | 0 | 0 | 0 | 0 | 18 | 0 |
| F16 | Výuka | 36 | 0 | 0 | 1 | 0 | 0 | 35 | 0 |
| F17 | Milník 1: první nasazení | 11 | 0 | 0 | 0 | 0 | 0 | 11 | 0 |
| F18 | Vlna 2: ceny a sklad | 18 | 0 | 0 | 0 | 0 | 0 | 18 | 0 |
| F19 | Speciální scénáře | 22 | 0 | 0 | 0 | 0 | 0 | 22 | 0 |
| F20 | Vlna 4: pokročilé konfigurace | 14 | 0 | 0 | 0 | 0 | 0 | 14 | 0 |
| F21 | Funkce, na kterých obchodník stojí | 22 | 0 | 0 | 0 | 0 | 0 | 22 | 0 |

## 3. Nejbližší kroky

- `[HOTOVO]` **F1-36** Odstraněny nepoužívané závislosti `vite` a `@nette/vite-plugin`; zachovány Sass a esbuild. `npm run build` úspěšně sestavil CSS i JS, `npm ls vite @nette/vite-plugin` potvrdil prázdný výsledek a `npm audit` nahlásil 0 zranitelností. Sass vypisuje deprecation warnings z Bootstrapu; nejde o chybu buildu.

### Aktivní a opravné úkoly
- `[K ÚPRAVĚ]` **F16-01** Lekce 1: Příprava prostředí a instalace – přejmenovat a ověřit.

### Nejbližší technické kroky
- `[HOTOVO]` **F1-30** Ověřit čisté naklonování a instalaci podle kontrolního seznamu z Lekce 1. Čistý klon vytvořen v samostatné složce; `composer install`, `npm ci`, Composer validace/audit, PHPStan level max, PHP-CS-Fixer, Deptrac, npm build a npm audit prošly. Po přidání `.gitattributes` byly konce řádků sjednoceny a `git status --short` zůstal prázdný.
- `[ROZDĚLÁNO]` **F1-32** Minimální funkční kostra Front a Admin: mapování presenterů, `HomePresenter` + Latte šablona, `DashboardPresenter` + Latte šablona a routování `/` a `/admin`; obě stránky zobrazí jednoduchý potvrzovací text.
- `[PLÁN]` **F1-33** Spike: automatická registrace DI extension jednotlivých modulů ze seznamu modulů.
- `[PLÁN]` **F1-34** Spike: načítání výchozích překladů modulů ze souborů.
- `[PLÁN]` **F1-35** Docker nebo DDEV konfigurace (po rozhodnutí F0).
- `[HOTOVO]` **F1-36** Odstranit Vite a jeho plugin z npm závislostí; build Sass + esbuild ověřen.
- `[HOTOVO]` **F1-37** Odstranit přímou závislost `nextras/orm` a nepoužívaný `OrmModel.php`; zachovat Nextras DBAL/Migrations. Ověřeny reference, Composer metadata a lock; `composer validate`, `composer check-platform-reqs` a PHPStan level max bez chyb.
- `[HOTOVO]` **F1-38** Přidat Monolog, nakonfigurovat strukturované logování a bezpečné maskování citlivých údajů podle F0-46. Ověřeno sestavení služby přes Nette DI jako `Psr\Log\LoggerInterface`, zápis strukturovaného JSON logu a maskování citlivého kontextu; PHPStan level max a Deptrac bez nálezů. HTTP provoz bude ověřen až po implementaci aplikace.
- `[HOTOVO]` **F1-39** Přidat a zavést `CHANGELOG.md` podle schválené politiky verzování F0-28. Zaveden formát založený na Keep a Changelog, pravidla pro obchodnicky relevantní změny, SemVer verze a Git tagy; první produkční vydání zatím neexistuje.
- `[HOTOVO]` **F1-18** Nainstalovat a nakonfigurovat Deptrac 4.7.2 pro kontrolu hranic modulů; ověřit pravidla pozitivním i negativním testem. Výsledná kontrola: 0 violations, 0 uncovered, 0 errors.
- `[HOTOVO]` **F1-20** Doplnit Composer skripty pro testy, coding standard a architekturu. Zachovány `phpstan` a `tester`, přidány `test`, `cs` a `arch`; vlastní skript `audit` byl odstraněn kvůli kolizi s vestavěným příkazem Composeru. Ověřeny `composer validate --no-check-publish`, `composer arch`, `composer cs` (0 souborů k opravě po formátování) a `composer audit` (0 bezpečnostních upozornění).
- `[HOTOVO]` **F1-22** Rozdělit zdroje front-endu na samostatné vstupy Front/Admin. Přidány `assets/scss/front.scss`, `admin.scss`, sdílený `_shared.scss` a `assets/js/front.js`, `admin.js`, sdílený `shared.js`; dokumentováno v `STRUKTURA.md`. Lokální build úspěšně ověřil všechny čtyři výstupy.
- `[HOTOVO]` **F1-24** Přidat `.nvmrc` s Node.js `26.13.0` a `engines` v `package.json` (`node: >=26.13.0 <27`, `npm: >=12.2.2 <13`); obě verze odpovídají lokálně ověřenému prostředí.
- `[HOTOVO]` **F1-25** Ověřit sestavení assetů a jejich reference v layoutech. `npm run build` úspěšně sestavil CSS i JS; kontrola obou Latte layoutů potvrdila odkazy na `front.css`/`front.js` a `admin.css`/`admin.js`.
- `[HOTOVO]` **F1-26** GitHub Actions workflow pro Composer úspěšně ověřen; všechny kontroly prošly.
- `[HOTOVO]` **F1-27** GitHub Actions workflow pro npm úspěšně ověřen; instalace, build i audit prošly.
- `[HOTOVO]` **F1-28** Dependabot přijat GitHubem; všech 5 prvních aktualizačních PR pro Composer, npm a GitHub Actions bylo po úspěšném CI sloučeno přes Squash and merge.

## 4. Plán po fázích

### F0: Rozhodnutí a dokumentace

_F0 je uzavřena: všech 54 rozhodovacích a dokumentačních položek je HOTOVO. Navazující implementační úkoly jsou rozdělené do příslušných technických fází._

- `[HOTOVO]` **F0-01** Zapsat vizi, hodnoty a pořadí priorit (ZAMER kap. 1)
- `[HOTOVO]` **F0-02** Potvrdit veřejný repozitář bez licence k užití; všechna práva vyhrazena; právní podmínky zůstávají otevřené (kap. 2)
- `[HOTOVO]` **F0-03** Rozhodnout architekturu: modulární monolit, jedna instance a jedna databáze na obchod (kap. 3)
- `[HOTOVO]` **F0-04** Zvolit technologie a pravidla pro přijímání závislostí (kap. 4)
- `[HOTOVO]` **F0-05** Zapsat bezpečnostní zásady (ZAMER, kap. 8)
- `[HOTOVO]` **F0-06** Rozhodnout zásady databáze: Explorer, MySQL, migrace, atributy, ID, překlady a měny (ZAMER, kap. 5)
- `[HOTOVO]` **F0-07** Sestavit 15 demo produktů a rozdělit je do čtyř vln (ZAMER, kap. 10)
- `[HOTOVO]` **F0-08** Rozhodnout demo data ve dvou jazycích (cs, en) a dvou měnách (CZK, EUR) (ZAMER, kap. 6)
- `[HOTOVO]` **F0-09** Schválit doménově rozdělené překlady; systémové výchozí texty jsou chráněné a administrátor spravuje vlastní překlady
- `[HOTOVO]` **F0-10** Ověřit přístup AI k veřejnému repozitáři na GitHubu
- `[HOTOVO]` **F0-11** Původní sjednocení názvu projektu v rané dokumentaci; finální oficiální název následně sjednocen rozhodnutím F0-43
- `[HOTOVO]` **F0-12** Sjednotit kanonickou dokumentaci a průběžné pravidlo její aktualizace
- `[HOTOVO]` **F0-13** Roadmap: verze 0.3 aktualizována po dokončení F3 migrací a uzavření DB rozhodnutí
- `[HOTOVO]` **F0-14** Kanonický dokument STRUKTURA odpovídá skutečnému stromu
- `[HOTOVO]` **F0-15** Schválit skutečnou strukturu `app/` podle stromu projektu
- `[HOTOVO]` **F0-16** Rozhodnout zachování režimu „všechna práva vyhrazena“ a požadavek na přípravu studentské licence/podmínek služby s právní kontrolou před distribucí či komerčním spuštěním; samotné právní dokumenty zůstávají předprodukčním úkolem
- `[HOTOVO]` **F0-17** Rozhodnout požadavek na DPA a GDPR dokumentaci pro hostované obchody; finální dokumenty a retenční pravidla před spuštěním ověří právník a specialista na ochranu osobních údajů- `[HOTOVO]` **F0-18** Vyhledávání: MySQL FULLTEXT jako výchozí, Elasticsearch/OpenSearch volitelně- `[HOTOVO]` **F0-19** Vývojové prostředí: Laragon na Windows jako hlavní prostředí; zachovat možnost budoucího spuštění v Dockeru nebo DDEV- `[HOTOVO]` **F0-20** Ceny se ukládají bez DPH; daň se vypočítává podle daňových pravidel a nastavení `tax_included` řídí způsob zobrazování cen- `[HOTOVO]` **F0-21** Jazyk v URL: všechny jazykové varianty mají explicitní prefix, včetně výchozího jazyka (`/cs`, `/en`)
- `[HOTOVO]` **F0-22** Režim B2B: první verze je B2C; datový a doménový model počítá s firemními zákazníky a IČO/DIČ, ověřování ARES/VIES a pokročilé B2B funkce přijdou později
- `[HOTOVO]` **F0-23** Platební integrace: vlastní rozhraní `PaymentProvider`; konkrétního poskytovatele vybrat až před produkčním nasazením
- `[HOTOVO]` **F0-24** Doručovací integrace: vlastní rozhraní `ShippingProvider`, samostatné adaptéry a možnost používat více dopravců současně
- `[HOTOVO]` **F0-25** Zvolit Sass + esbuild bez Vite
- `[HOTOVO]` **F0-26** Datagrid pro administraci: zvolit Ublaboo Datagrid; zatím bez implementace a konfigurace, s vlastní aplikační vrstvou pro možnost budoucí náhrady
- `[HOTOVO]` **F0-27** Formát ukládání textů: ukládat čistý Markdown; při vykreslení převést na HTML a výsledné HTML vyčistit přes HTMLPurifier nebo schválený ekvivalent
- `[HOTOVO]` **F0-28** Politika verzování a changelog pro hostované obchody: SemVer, Git tagy produkčních vydání, `CHANGELOG.md`, přehled změn pro obchodníky, společné verzování platformy a dopředné databázové migrace
- `[HOTOVO]` **F0-29** Způsob generování faktur a PDF: zvoleno mPDF; PDF se generují ze spravovaných šablon, vycházejí z neměnného snímku objednávky a vystavené doklady se uchovávají beze změn
- `[HOTOVO]` **F0-30** Miniatury: WebP jako výchozí formát, AVIF jako doplňková optimalizace; zachovat originály, centrálně konfigurovat velikosti a kvalitu, podporovat ořez, poměr stran a responzivní varianty
- `[HOTOVO]` **F0-31** Fronty a plánované úlohy: databázová fronta zpracovávaná workerem, jehož spouštění zajišťuje cron; možnost pozdějšího nasazení samostatných či více workerů
- `[HOTOVO]` **F0-32** Šablony e-mailů: Latte šablony uložené v souborech repozitáře, verzované společně s aplikací; bez přímých úprav zdrojových šablon v administraci
- `[HOTOVO]` **F0-33** Zvolit kódování a kolaci DB: `utf8mb4` a `utf8mb4_0900_ai_ci`
- `[HOTOVO]` **F0-34** Rozhodnout způsob reprezentace peněz: celočíselné minor units (haléře/centy)
- `[HOTOVO]` **F0-35** Rozhodnout způsob reprezentace množství: pevná desetinná místa v DB, žádný `float`
- `[HOTOVO]` **F0-36** Rozhodnout formát identifikátorů: interní auto-increment ID a veřejné ULID
- `[HOTOVO]` **F0-37** Revidovat a schválit závazný seznam 15 demo produktů ve čtyřech vlnách (ZAMER, kap. 10).
- `[HOTOVO]` **F0-38** Rozhodnout architekturu exportních feedů: společné interní rozhraní a samostatné adaptéry pro Heureku, Zboží.cz a Google Shopping (ZAMER, kap. 12).
- `[HOTOVO]` **F0-39** Rozhodnout architekturu importu produktů: společné interní rozhraní, adaptéry CSV/XML, validace, náhled před potvrzením a protokol výsledků včetně chyb (ZAMER, kap. 13).
- `[HOTOVO]` **F0-40** Stanovit výchozí SLA hostované služby: dostupnost 99,5 % měsíčně, monitoring, oznámená údržba a reakční doby; bez finančních kompenzací v této fázi (ZAMER, kap. 14).
- `[HOTOVO]` **F0-41** Stanovit zásady zálohování a obnovy: denní plné zálohy, průběžné binlogy, oddělené šifrované uložení a pravidelné testy obnovy; RPO/RTO určit pro konkrétní prostředí (ZAMER, kap. 15).
- `[HOTOVO]` **F0-42** Stanovit pravidla Omnibus pro historii cen: automatické určení nejnižší ceny za předchozích 30 dnů, uchování doložitelné historie a zohlednění zákonných výjimek (ZAMER, kap. 17).
- `[HOTOVO]` **F0-43** Oficiální název projektu: **ArgoMi-BestShop**; databáze zůstává `argomi_bestshop`, technický repozitář a projektová cesta `argomi-bestshop` se nemění.
- `[HOTOVO]` **F0-44** Promítnout schválenou strukturu `app/` a rozhodnutí o překladech do ZAMĚRU
- `[HOTOVO]` **F0-45** Nebudou podporovány instalovatelné pluginy třetích stran; funkce patří do vlastního jádra a interních modulů, externí služby se napojují přes interní adaptéry. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-46** Standard logování: Tracy pro vývojovou diagnostiku, Monolog pro aplikační logy, strukturované úrovně DEBUG/INFO/WARNING/ERROR/CRITICAL, bez citlivých údajů; Sentry případně později. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-47** Strategie větví: během současného vývoje se pracuje přímo ve větvi `main` s průběžnou lokální zálohou. Po dokončení a prvním vydání se přejde na stabilní `main`, samostatné vývojové větve, slučování přes Pull Request a verzovací Git tagy podle SemVer. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-48** Závazný cíl přístupnosti zákaznického rozhraní i administrace: WCAG 2.1 AA. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-49** Pravidla mazání a retence: zachování zákonně uchovávaných objednávek a dokladů, GDPR anonymizace/odstranění zákaznických údajů, časově omezené logy s automatickým mazáním a ochrana auditních záznamů. Konkrétní lhůty dle práva a provozu. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-50** Kontrola licencí přímých i nepřímých PHP/JavaScript závislostí, přednostně MIT/BSD/Apache 2.0 a dalších licencí slučitelných s komerčním použitím; evidence povinností a kontrola před každým vydáním. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-51** Sjednotit právní metadata Composer/npm s režimem všechna práva vyhrazena
- `[HOTOVO]` **F0-52** Aktualizovat README a AGENTS.md podle skutečného projektu, bez instrukcí skeletonu
- `[HOTOVO]` **F0-53** Demo data budou označená a oddělená; demo objednávky nespustí reálné platby, expedici ani externí operace. Testovací integrace poběží jen v testovacím režimu a produkce bude chráněna proti demo konfiguraci. Podrobnosti v ZAMER.md.
- `[HOTOVO]` **F0-54** Rozhodnuto: Nette Database Explorer bude jedinou databázovou vrstvou běžného aplikačního kódu. Nextras ORM ani `OrmModel.php` do cílové architektury nepatří a po ověření závislostí budou odstraněny. Nextras Migrations/DBAL mohou zůstat jako samostatné nástroje pro migrace a testování. Podrobnosti v ZAMER.md.


### F1: Prostředí a repozitář

_Technické zázemí, nástroje kvality kódu a základní kostra projektu._ Hotovo 35 z 39 kroků; 1 krok je rozpracován.

- `[HOTOVO]` **F1-01** Dokončit úklid výchozí Nette kostry a sjednotit metadata projektu
- `[HOTOVO]` **F1-02** Ověřit verze PHP, Composeru, Node.js, MySQL a Gitu (kontrola z Lekce 1)
- `[HOTOVO]` **F1-03** Povolit a ověřit potřebná PHP rozšíření (`pdo_mysql`, `mbstring`, `intl`, `openssl`, `curl`, `fileinfo`, `zip`, `gd`)
- `[HOTOVO]` **F1-04** Nastavit document root na `www/` ve virtuálním hostu
- `[HOTOVO]` **F1-05** Založit veřejný GitHub repozitář bez licence k užití; všechna práva vyhrazena
- `[HOTOVO]` **F1-06** Doplnit a ověřit `.gitignore` (vendor, node_modules, temp, log, local.neon, www/assets)
- `[HOTOVO]` **F1-07** Přidat `.editorconfig`
- `[HOTOVO]` **F1-08** Ověřit vývojovou konfiguraci PHP v CLI i Apache (PHP 8.5.12, `memory_limit=512M`, `E_ALL`, zobrazování a logování chyb; konfigurace `php.ini` vyhovuje, bez změn)
- `[HOTOVO]` **F1-09** `composer.json`: nastavit minimální PHP 8.3, `config.platform.php=8.3.0`, `sort-packages`; zachovat existující skripty `phpstan` a `tester`; ověřit Composer validaci a platform requirements bez změny verzí závislostí
- `[HOTOVO]` **F1-10** Ověřit databázi `argomi_bestshop` (`utf8mb4`, `utf8mb4_0900_ai_ci`) a vytvořit oddělené účty aplikace a migrací s minimálními oprávněními; přihlášení obou účtů ověřeno
- `[HOTOVO]` **F1-11** Vytvořit `config/local.neon` a ověřit jeho načítání v `Bootstrap.php`
- `[HOTOVO]` **F1-12** Ověřit přímou závislost `nette/database` v `composer.json` a `composer.lock` (verze 3.2.10); instalace i uzamčená verze jsou shodné.
- `[HOTOVO]` **F1-13** Nainstalovat PHPStan 2.3.5, `extension-installer` 1.4.3, `phpstan-nette` 2.0.15, `phpstan-strict-rules` 2.1.1 a `phpstan-deprecation-rules` 2.0.5; automatická registrace rozšíření ověřena Composerem
- `[HOTOVO]` **F1-14** Vytvořit `phpstan.neon` s `level: max`
- `[HOTOVO]` **F1-15** Opravit chyby kostry na úrovni max (bez baseline)
- `[HOTOVO]` **F1-16** Nastavit coding standard PER-CS 3.0: PHP-CS-Fixer 3.95.29 jako vývojová závislost, konfigurace `.php-cs-fixer.dist.php` používá pevnou sadu `@PER-CS3.0`; všech 6 existujících PHP souborů naformátováno a následná kontrola bez nálezů. PHPStan level max zůstává čistý.
- `[HOTOVO]` **F1-17** Nainstalovat testovací nástroj (po rozhodnutí F0)
- `[HOTOVO]` **F1-18** Nainstalovat a nakonfigurovat Deptrac 4.7.2; pozitivní i negativní test potvrdil správné hlídání hranic modulů, finální analýza bez violations, uncovered a errors.
- `[HOTOVO]` **F1-19** Nainstalovat konzolový nástroj (po rozhodnutí F0)
- `[HOTOVO]` **F1-20** Skripty v `composer.json`: `phpstan`, `tester`, `test`, `cs`, `arch`. Bez vlastního `audit` skriptu kvůli kolizi s vestavěným příkazem `composer audit`; všechny relevantní příkazy ověřeny lokálně.
- `[HOTOVO]` **F1-21** `npm init`, instalace `sass`, `esbuild`, `bootstrap`, `@popperjs/core`, `naja`
- `[HOTOVO]` **F1-22** Rozdělit zdroje front-endu na samostatné vstupy `Front` a `Admin` v `assets/scss` a `assets/js`; společný základ Sassu a inicializace JS jsou oddělené do sdílených souborů. Lokální build úspěšně ověřil všechny čtyři výstupy.
- `[HOTOVO]` **F1-23** Skripty `build:css`, `build:js`, `build`, `watch:css` a `watch:js` v `package.json` sestavují samostatné výstupy `front.css`, `admin.css`, `front.js` a `admin.js`; `npm run build` proběhl úspěšně a všechny čtyři výstupy mají nenulovou velikost.
- `[HOTOVO]` **F1-24** `.nvmrc` obsahuje Node.js `26.13.0`; `package.json` deklaruje `engines.node` `>=26.13.0 <27` a `engines.npm` `>=12.2.2 <13`.
- `[HOTOVO]` **F1-25** Ověřit sestavení a reference assetů v layoutech. `npm run build` proběhl úspěšně; `app/UI/Front/@layout.latte` odkazuje na `front.css` a `front.js`, `app/UI/Admin/@layout.latte` na `admin.css` a `admin.js`.
- `[HOTOVO]` **F1-26** GitHub Actions: `composer validate`, `composer audit`, PHPStan, coding standard a Deptrac. Workflow `.github/workflows/composer.yml` úspěšně proběhl na GitHubu (push, běh č. 1); všechny kroky včetně instalace závislostí skončily úspěšně. Automatické testy budou doplněny, až bude v projektu připravena testovací sada.
- `[HOTOVO]` **F1-27** GitHub Actions: `npm ci`, build CSS/JavaScript a `npm audit`. Workflow `.github/workflows/npm.yml` úspěšně proběhl na GitHubu (push, běh č. 1); všechny kroky včetně ověření Node.js/npm verzí skončily úspěšně.
- `[HOTOVO]` **F1-28** Pravidelná kontrola aktualizací závislostí. Soubor `.github/dependabot.yml` nastavuje týdenní kontroly Composer, npm a GitHub Actions každé pondělí v 06:00 (Europe/Berlin); minor/patch aktualizace seskupuje podle ekosystému, major aktualizace ponechává samostatně. Všech 5 prvních PR bylo po úspěšných CI kontrolách sloučeno přes Squash and merge; změny byly staženy a lokálně ověřeny.
- `[HOTOVO]` **F1-29** README projektu: jak rozjet projekt na pár příkazů
- `[HOTOVO]` **F1-30** Ověřit čisté naklonování a rozjetí podle kontrolního seznamu z Lekce 1. V samostatné kontrolní složce proběhly `composer install`, `npm ci`, `composer validate --no-check-publish`, `composer audit`, `composer phpstan`, `composer cs`, `composer arch`, `npm run build` a `npm audit`. Po doplnění `.gitattributes` byla pracovní kopie čistá (`git status --short` bez výstupu).
- `[HOTOVO]` **F1-31** Vytvořit adresářovou strukturu podle STRUKTURA
- `[ROZDĚLÁNO]` **F1-32** Vytvořit minimální funkční kostru Front a Admin: ověřit mapování presenterů v Nette, vytvořit `HomePresenter` a jeho `default.latte` s textem „Ahoj, tady Front“, vytvořit `DashboardPresenter` a jeho `default.latte` s textem „Ahoj, tady administrace“, nastavit routování hlavní stránky a `/admin` a ověřit obě stránky přes HTTP. Neimplementovat zatím aplikační logiku.
- `[PLÁN]` **F1-33** Spike: automatická registrace DI extension jednotlivých modulů ze seznamu modulů
- `[PLÁN]` **F1-34** Spike: načítání výchozích překladů modulů ze souborů
- `[PLÁN]` **F1-35** Docker nebo DDEV konfigurace (po rozhodnutí F0)
- `[HOTOVO]` **F1-36** Odstranit nepoužívané balíčky Vite a Vite pluginu z `package.json`/`package-lock.json`; Sass + esbuild build ověřen jako úspěšný. Vite balíčky nejsou v závislostech.
- `[HOTOVO]` **F1-37** Odstranit nepoužívaný `app/Shared/Infrastructure/Persistence/OrmModel.php` a přímou závislost `nextras/orm`; ověřit reference, aktualizovat Composer lock a zachovat Nextras DBAL/Migrations. `composer validate`, `composer check-platform-reqs` a PHPStan level max bez chyb; automatické testy zatím nejsou v projektu.
- `[HOTOVO]` **F1-38** Přidat Monolog, nakonfigurovat strukturované logování a bezpečné maskování citlivých údajů podle F0-46. Ověřeno sestavení služby přes Nette DI jako `Psr\Log\LoggerInterface`, zápis strukturovaného JSON logu a maskování citlivého kontextu; PHPStan level max a Deptrac bez nálezů. HTTP provoz bude ověřen až po implementaci aplikace.
- `[HOTOVO]` **F1-39** Přidat a zavést `CHANGELOG.md` podle schválené politiky verzování F0-28

### F2: Sdílené jádro (Shared)

_Malá sada hodnotových objektů a rozhraní, na kterých stojí všechny moduly. Bez znalosti jednotlivých modulů._ Hotovo 0 z 16 kroků.

- `[PLÁN]` **F2-01** Vytvořit `app/Shared` (Domain, Infrastructure)
- `[PLÁN]` **F2-02** `Money`: částka v nejmenších jednotkách plus měna, sčítání, násobení, porovnání, výjimka při různých měnách
- `[PLÁN]` **F2-03** `Currency`: kód, počet desetinných míst, `CurrencyProvider` (výchozí, aktivní)
- `[PLÁN]` **F2-04** `Quantity`: decimal s pevnou přesností, bez `float`
- `[PLÁN]` **F2-05** `Percentage` / sazba v basis points
- `[PLÁN]` **F2-06** `Locale`: kód jazyka, `LocaleProvider` (výchozí, aktivní)
- `[PLÁN]` **F2-07** `Clock` (UTC) a testovací implementace
- `[PLÁN]` **F2-08** Generátor a hodnotový objekt veřejného ID (ULID)
- `[PLÁN]` **F2-09** Základ výjimek: doménová výjimka, nenalezeno, neplatný vstup
- `[PLÁN]` **F2-10** Rozhraní `DomainEvent` a synchronní `EventDispatcher`
- `[PLÁN]` **F2-11** Registrace posluchačů událostí přes DI tagy
- `[PLÁN]` **F2-12** Rozhraní `Transaction` (spuštění v transakci)
- `[PLÁN]` **F2-13** Typované neměnné kolekce pro DTO
- `[PLÁN]` **F2-14** Jednotkové testy: Money, Quantity, Locale, Clock, ULID
- `[PLÁN]` **F2-15** Architektonický test: `Shared` nezávisí na žádném modulu
- `[PLÁN]` **F2-16** Dokumentace: co do `Shared` patří a co ne

### F3: Databáze a migrace

_Schéma vlny 1 spuštěné, ověřené a napojené na Nette Database Explorer._ Hotovo 9 z 32 kroků.

- `[HOTOVO]` **F3-01** Počáteční schéma, základní data a demo data v časově značených Nextras migracích načtené v MySQL bez chyb
- `[HOTOVO]` **F3-02** Nainstalovat Nextras Migrations a nastavit adresáře `structures`, `basic-data`, `dummy-data` (integrováno v Nette DI)
- `[HOTOVO]` **F3-03** Spustit `2026-11-04-200000-init.sql` v MySQL 8: proběhlo bez chyb (ověřil autor)
- `[ZRUŠENO]` **F3-04** Opravit chyby `001` (žádné nebyly)
- `[HOTOVO]` **F3-05** Rozhodnout, zda `001` rozdělit na migrace po modulech (rozhodnuto ponechat monolit pro vlnu 1, dále inkrementálně)
- `[HOTOVO]` **F3-06** Spustit základní a demo seed migrace: proběhlo bez chyb (ověřil autor)
- `[PLÁN]` **F3-07** Ověřit konvence Exploreru (`ref()`, `related()`) na reálné databázi
- `[PLÁN]` **F3-08** Test: každá tabulka má `id` a cizí klíče odpovídají konvenci
- `[PLÁN]` **F3-09** Test: výjimky (`category.parent_id`, `product_relation.related_product_id`)
- `[PLÁN]` **F3-10** Základ repozitáře nad Explorer: mapování `ActiveRow` na DTO (dotazy pouze v repozitářích, žádné v presenterech)
- `[PLÁN]` **F3-11** Typované `readonly` DTO pro entity vlny 1
- `[PLÁN]` **F3-12** Implementace `Transaction` nad `Nette\Database\Connection`- `[PLÁN]` **F3-13** Tracy panel pro SQL dotazy ve vývoji
- `[PLÁN]` **F3-14** Kontrola, že žádný dotaz neskládá SQL z řetězců- `[PLÁN]` **F3-15** Testovací databáze a bootstrap integračních testů
- `[HOTOVO]` **F3-16** Bezpečný vývojový `db:reset`: ověření databáze `argomi_bestshop` a lokálního účtu `argomi_migrator`, dvojí potvrzení, odstranění tabulek/pohledů bez DROP/CREATE databáze a opětovné spuštění skupin structures → basic-data → dummy-data (`composer db:reset`); lokálně ověřeno úspěšné odstranění 47 objektů a dokončení všech 3 migrací. Následná kontrola přes Nette Database Explorer potvrdila 3 demo produkty, 2 jazyky a 2 měny; PHPStan bez chyb.- `[HOTOVO]` **F3-17** Příkaz `db:migrate` pro nasazení (rozhodnuto automaticky v CI/CD pipeline přes `migrations:continue`)
- `[HOTOVO]` **F3-18** Příkaz pro spuštění migrací z konzole (`php bin/console migrations:continue` přes Contributte Console)- `[PLÁN]` **F3-19** Implementace hashe kombinace varianty (SHA-256) a test shody s SQL `SHA2(..., 256)`
- `[PLÁN]` **F3-20** Diagram vztahů tabulek rozdělený po modulech
- `[HOTOVO]` **F3-21** Oddělit databázové účty aplikace (`argomi_app`, pouze CRUD) a migrací (`argomi_migrator`, CRUD + DDL); oba účty mají přístup pouze k `argomi_bestshop` a přihlášení bylo ověřeno
- `[HOTOVO]` **F3-22** Zásady zálohování a obnovy databáze (před migrací povinný dump/snapshot, rollback obnovením ze zálohy)
- `[PLÁN]` **F3-23** CI: ověřit, že schéma prochází na verzi MySQL, kterou používáme (včetně `CHECK` u sloupců s cizím klíčem)
- `[PLÁN]` **F3-24** Test: smazání hodnoty atributu nebo osy variant nesmí potichu odebrat hodnoty variant (rozhodnout `CASCADE` a `RESTRICT`)
- `[PLÁN]` **F3-25** Dokumentace schématu `docs/schema.md`: komentáře, pravidlo hashe varianty, pravidla cizích klíčů
- `[PLÁN]` **F3-26** Cizí klíč na `language(code)` u všech sloupců `locale` (dnes jen `cart` a `shop_order`)
- `[PLÁN]` **F3-27** Kontroly součtů objednávky v databázi (`grand_total`, `line_gross`), případně sloupec `adjustments_net_minor`
- `[PLÁN]` **F3-28** Pravidlo mazání: archivace místo mazání produktů a variant (`RESTRICT` u `product_variant.product_id`)
- `[PLÁN]` **F3-29** Test: přepnutí výchozí varianty v transakci (`default_flag` s unikátním klíčem)
- `[PLÁN]` **F3-30** Demo data v dalším vydání: plné texty produktů, úplné snímky objednávek (atributy, adresy), počáteční pohyby skladu, další systémové texty
- `[PLÁN]` **F3-31** Navrhnout a migrovat doménově rozdělené systémové překlady ze současné tabulky `translation_message`
- `[PLÁN]` **F3-32** Navrhnout a implementovat explicitní označení demo dat a zákaz vedlejších efektů pro demo objednávky

### F4: Překlady v databázi

_Modul Translation: systémové texty, jednotky, obsah entit, přepsání administrátorem._ Hotovo 0 z 29 kroků.

- `[PLÁN]` **F4-01** Modul `Translation`: struktura a DI extension
- `[PLÁN]` **F4-02** `MessageStore`: načtení domény a jazyka jedním dotazem
- `[PLÁN]` **F4-03** Cache domén (Nette Cache) a zneplatnění po uložení
- `[PLÁN]` **F4-04** Implementace `Nette\Localization\Translator` nad úložištěm
- `[PLÁN]` **F4-05** Formátování ICU MessageFormat přes `intl`
- `[PLÁN]` **F4-06** Řetězec záloh: požadovaný jazyk, výchozí jazyk, klíč
- `[PLÁN]` **F4-07** Logování chybějících překladů a Tracy panel
- `[PLÁN]` **F4-08** Latte rozšíření: překlad klíče v šabloně
- `[PLÁN]` **F4-09** Pomocník pro překlad entit (překladová řádka se zálohou na výchozí jazyk)
- `[PLÁN]` **F4-10** Formátování peněz a čísel (`NumberFormatter`) podle jazyka
- `[PLÁN]` **F4-11** `UnitFormatter`: symbol a název s množným číslem
- `[PLÁN]` **F4-12** Struktura souborů s výchozími texty modulu (`Resources/translations`)
- `[PLÁN]` **F4-13** Importér výchozích textů do `translation_message`
- `[PLÁN]` **F4-14** Logika `is_customized`, `default_hash`, `is_outdated` a testy všech stavů
- `[PLÁN]` **F4-15** Příkaz `translations:import`
- `[PLÁN]` **F4-16** Příkaz `translations:check` (chybějící a nepoužité klíče)
- `[PLÁN]` **F4-17** Admin: seznam textů s filtrem domény, jazyka a stavu
- `[PLÁN]` **F4-18** Admin: správa vlastních překladů a zneplatnění cache; systémové zdroje zůstávají chráněné
- `[PLÁN]` **F4-19** Admin: přehled změněných systémových zdrojů a vlastních překladů; bez přímé editace systémového zdroje
- `[PLÁN]` **F4-20** Admin: vytvoření vlastního textu v povolené doméně
- `[PLÁN]` **F4-21** Admin: přidání a aktivace nového jazyka
- `[PLÁN]` **F4-22** Oprávnění k úpravě překladů
- `[PLÁN]` **F4-23** Bezpečnost: texty jsou prostý text, test escapování, zákaz HTML
- `[PLÁN]` **F4-24** Texty modulu (cs, en)
- `[PLÁN]` **F4-25** Dokumentace modulu Translation
- `[PLÁN]` **F4-26** Oddělit systémové překlady jednotlivých domén do cílového úložiště
- `[PLÁN]` **F4-27** Implementovat chráněné systémové zdroje a oddělené vlastní překlady
- `[PLÁN]` **F4-28** Migrovat současná data z `translation_message` bez ztráty
- `[PLÁN]` **F4-29** Testy oprávnění: administrátor nesmí měnit systémové zdroje

### F5: Catalog (vlna 1)

_Kategorie, produkty, varianty, atributy a volby zákazníka pro demo produkty 1 až 5._ Hotovo 0 z 36 kroků.

- `[PLÁN]` **F5-01** Modul `Catalog`: struktura a DI extension
- `[PLÁN]` **F5-02** Doménové objekty: `Category`, `Product`, `ProductVariant`, `Attribute`, `CustomerOption`
- `[PLÁN]` **F5-03** `CategoryRepository`: strom kategorií, aktivní kategorie, řazení
- `[PLÁN]` **F5-04** `ProductRepository`: výpis produktů v kategorii, stránkování, řazení
- `[PLÁN]` **F5-05** `ProductDetailService`: načtení detailu produktu (překlady, varianty, obrázky, atributy, volby)
- `[PLÁN]` **F5-06** `VariantSelector`: vyhledání varianty podle zvolených hodnot os
- `[PLÁN]` **F5-07** `OptionResolver`: validace a výpočet ceny voleb zákazníka
- `[PLÁN]` **F5-08** Repozitář obrázků a vazba na varianty; zachování originálů a generování responzivních WebP/AVIF variant podle F0-30
- `[PLÁN]` **F5-09** Správa souvisejících produktů (`product_relation`)
- `[PLÁN]` **F5-10** Generování a kontrola slugů (unikátnost v rámci jazyka)
- `[PLÁN]` **F5-11** Výpočet skladové dostupnosti souhrnně za produkt
- `[PLÁN]` **F5-12** Události katalogu (`ProductCreated`, `ProductUpdated`, `ProductDeleted`)
- `[PLÁN]` **F5-13** Admin: formulář vytvoření a úpravy kategorie
- `[PLÁN]` **F5-14** Admin: strom kategorií s drag & drop řazením
- `[PLÁN]` **F5-15** Admin: seznam produktů s filtry (kategorie, stav, typ)
- `[PLÁN]` **F5-16** Admin: základní záložka produktu (názvy, popisy, zařazení)
- `[PLÁN]` **F5-17** Admin: matice variant (generování kombinací z os)
- `[PLÁN]` **F5-18** Admin: úprava konkrétní varianty (SKU, EAN, váha, výchozí)
- `[PLÁN]` **F5-19** Admin: správa atributů a jejich hodnot
- `[PLÁN]` **F5-20** Admin: přiřazení atributů k produktu
- `[PLÁN]` **F5-21** Admin: konfigurace voleb zákazníka pro produkt
- `[PLÁN]` **F5-22** Admin: nahrání obrázků a přiřazení k variantám
- `[PLÁN]` **F5-23** Admin: správa souvisejících a doporučených produktů
- `[PLÁN]` **F5-24** Validace: kontrola povinných údajů při aktivaci produktu
- `[PLÁN]` **F5-25** Zákaz přímého mazání: označení produktu jako archivovaný
- `[PLÁN]` **F5-26** Oprávnění pro správu katalogu
- `[PLÁN]` **F5-27** Test: načtení demo produktu 1 – bavlněné tričko (varianty velikost × barva)
- `[PLÁN]` **F5-28** Test: načtení demo produktu 2 – GaN nabíječka (varianty výkon × počet portů × barva)
- `[PLÁN]` **F5-29** Test: načtení demo produktu 3 – USB-C kabel (varianty délky, konektorů a barvy)
- `[PLÁN]` **F5-30** Test generování správného hashe kombinace varianty
- `[PLÁN]` **F5-31** Jednotkové testy pro `VariantSelector` a `OptionResolver`
- `[PLÁN]` **F5-32** Texty modulu Catalog (cs, en)
- `[PLÁN]` **F5-33** Šablony komponent katalogu pro Front i Admin
- `[PLÁN]` **F5-34** Dokumentace modulu Catalog
- `[PLÁN]` **F5-35** Test: načtení demo produktu 4 – kniha / výběrová káva (jednoduchý produkt s výchozí variantou)
- `[PLÁN]` **F5-36** Test: načtení demo produktu 5 – hrnek s vlastním potiskem (zákaznické volby a příplatky)

### F6: Tax, Pricing, Inventory

_Ceny, daně a skladové hospodářství pro vlnu 1._ Hotovo 0 z 27 kroků.

- `[PLÁN]` **F6-01** Modul `Tax`: struktura, `TaxFacade`
- `[PLÁN]` **F6-02** Daňové třídy a sazby podle země a data platnosti
- `[PLÁN]` **F6-03** `TaxCalculator`: DPH z ceny s DPH i bez DPH
- `[PLÁN]` **F6-04** Zaokrouhlování podle rozhodnutí z F0 a testy
- `[PLÁN]` **F6-05** Testy sazeb 21 % a 12 % a hraničních dat platnosti
- `[PLÁN]` **F6-06** Modul `Pricing`: struktura, `PricingFacade`
- `[PLÁN]` **F6-07** Cena varianty v měně jako `Money`
- `[PLÁN]` **F6-08** Příplatky za volby zákazníka
- `[PLÁN]` **F6-09** Jednotková cena konfigurace (základ plus příplatky)
- `[PLÁN]` **F6-10** Chování při chybějící ceně v měně (chyba, ne přepočet)
- `[PLÁN]` **F6-11** Cena dopravy po měnách a hranice dopravy zdarma
- `[PLÁN]` **F6-12** Admin: zadání ceníků po měnách
- `[PLÁN]` **F6-13** Testy cen v CZK a EUR
- `[PLÁN]` **F6-14** Pravidlo PHPStan proti `float` u peněz
- `[PLÁN]` **F6-15** Modul `Inventory`: struktura, `InventoryFacade`
- `[PLÁN]` **F6-16** Dostupnost varianty (skladem, vyprodáno, dobjednání)
- `[PLÁN]` **F6-17** Rezervace skladu (vytvoření, uvolnění, vypršení)
- `[PLÁN]` **F6-18** Odpis skladu při objednávce
- `[PLÁN]` **F6-19** Pohyby skladu pro každou změnu
- `[PLÁN]` **F6-20** Souběh: dvě objednávky na poslední kus (zámek nebo podmíněný update) a test- `[PLÁN]` **F6-21** Události `StockLow` a `OutOfStock`
- `[PLÁN]` **F6-22** Admin: příjem zboží a korekce skladu
- `[PLÁN]` **F6-23** Testy rezervace a odpisu- `[PLÁN]` **F6-24** Texty modulů (cs, en)
- `[PLÁN]` **F6-25** Dokumentace modulů Tax, Pricing, Inventory
- `[PLÁN]` **F6-26** Pricing: zápis `variant_price_history` při každé změně ceny (včetně zdroje změny)- `[PLÁN]` **F6-27** Pricing: nejnižší cena za posledních 30 dní při zobrazení slevy (právní pravidlo, konzultace)

### F7: Cart
_Košík jako doménový objekt, ceny přes Pricing a Tax._ Hotovo 0 z 16 kroků.

- `[PLÁN]` **F7-01** Modul `Cart`: struktura, `CartFacade`
- `[PLÁN]` **F7-02** Doména: `Cart`, `CartItem`, volby položky
- `[PLÁN]` **F7-03** Košík hosta (veřejné ID v bezpečné cookie)
- `[PLÁN]` **F7-04** Přidání, změna množství, odebrání položky
- `[PLÁN]` **F7-05** Validace: varianta existuje, je aktivní a skladem, povinné volby vyplněny
- `[PLÁN]` **F7-06** Slučování stejných položek (varianta a volby)
- `[PLÁN]` **F7-07** Přepočet cen a součtů přes Pricing a Tax
- `[PLÁN]` **F7-08** Měna a jazyk košíku (změna měny znamená nový výpočet)
- `[PLÁN]` **F7-09** Sloučení košíku hosta po přihlášení
- `[PLÁN]` **F7-10** Vypršení košíků a úklid (příkaz)
- `[PLÁN]` **F7-11** Ukazatel „do dopravy zdarma zbývá“ (první pobídka v jádru)
- `[PLÁN]` **F7-12** Události košíku
- `[PLÁN]` **F7-13** Texty (cs, en)
- `[PLÁN]` **F7-14** Jednotkové a integrační testy
- `[PLÁN]` **F7-15** Bezpečnost: CSRF u změn, omezení množství
- `[PLÁN]` **F7-16** Dokumentace modulu

### F8: Zákazníci a administrátoři

_Dvě oddělené identity: zákazník na frontu a administrátor v administraci._ Hotovo 0 z 21 kroků.

- `[PLÁN]` **F8-01** Modul `Customer`: struktura, `CustomerFacade`
- `[PLÁN]` **F8-02** Registrace zákazníka s ověřením e-mailu
- `[PLÁN]` **F8-03** Hashování hesel (`Nette\Security\Passwords`) a přehashování
- `[PLÁN]` **F8-04** Přihlášení, odhlášení, zapamatování
- `[PLÁN]` **F8-05** Omezení počtu pokusů o přihlášení
- `[PLÁN]` **F8-06** Obnova hesla (jednorázový token s expirací)
- `[PLÁN]` **F8-07** Správa adres zákazníka
- `[PLÁN]` **F8-08** Preferovaný jazyk zákazníka
- `[PLÁN]` **F8-09** Přehled objednávek v účtu (čte Orders přes API)
- `[PLÁN]` **F8-10** GDPR: export dat a anonymizace účtu
- `[PLÁN]` **F8-11** Modul `Identity` pro administrátory (oddělený od zákazníků)
- `[PLÁN]` **F8-12** Autentizace administrátora (oddělená session a cookie)
- `[PLÁN]` **F8-13** Role a oprávnění (ACL), seznam oprávnění po modulech
- `[PLÁN]` **F8-14** Dvoufaktorové ověření pro všechny administrátorské účty
- `[PLÁN]` **F8-15** Audit přihlášení a změn v administraci
- `[PLÁN]` **F8-16** Politika session (regenerace ID, časové limity, SameSite, Secure, HttpOnly)
- `[PLÁN]` **F8-17** Admin: správa uživatelů a rolí
- `[PLÁN]` **F8-18** Texty (cs, en)
- `[PLÁN]` **F8-19** Testy autentizace a oprávnění
- `[PLÁN]` **F8-20** Ruční bezpečnostní kontrola přihlášení
- `[PLÁN]` **F8-21** Dokumentace

### F9: Orders a Checkout

_Objednávka jako snímek, stavový automat, průchod pokladnou._ Hotovo 0 z 27 kroků.

- `[PLÁN]` **F9-01** Modul `Orders`: struktura, `OrdersFacade`
- `[PLÁN]` **F9-02** Doména: `Order`, `OrderItem` jako snímek
- `[PLÁN]` **F9-03** Stavový automat objednávky (stavy, přechody, zakázané přechody)
- `[PLÁN]` **F9-04** Záznam každé změny stavu do historie
- `[PLÁN]` **F9-05** Vytvoření objednávky z košíku (snímek názvů, cen, sazeb, voleb, adres)
- `[PLÁN]` **F9-06** Rozpočet základní ceny a příplatků (`base_unit_price_minor`, `options_unit_total_minor`)
- `[PLÁN]` **F9-07** Generování čísla objednávky (unikátní, odolné proti souběhu)
- `[PLÁN]` **F9-08** Události `OrderPlaced`, `OrderPaid`, `OrderCancelled`
- `[PLÁN]` **F9-09** Zrušení objednávky a uvolnění rezervace skladu
- `[PLÁN]` **F9-10** Poznámky zákazníka a interní komentáře
- `[PLÁN]` **F9-11** Admin: seznam a detail objednávky, změna stavu
- `[PLÁN]` **F9-12** Testy stavového automatu a snímku (změna katalogu nemění starou objednávku)
- `[PLÁN]` **F9-13** Překlad názvů stavů v databázi
- `[PLÁN]` **F9-14** Texty (cs, en)
- `[PLÁN]` **F9-15** Modul `Checkout`: struktura, `CheckoutFacade`
- `[PLÁN]` **F9-16** Průchod pokladnou: kontakt, adresy, doprava, platba, shrnutí
- `[PLÁN]` **F9-17** Host a přihlášený zákazník
- `[PLÁN]` **F9-18** Výběr dopravy a platby podle měny, zakázané kombinace
- `[PLÁN]` **F9-19** Souhlasy (obchodní podmínky, zpracování údajů) se záznamem času a verze
- `[PLÁN]` **F9-20** Přepočet před odesláním a kontrola dostupnosti
- `[PLÁN]` **F9-21** Ochrana proti dvojímu odeslání (idempotence)
- `[PLÁN]` **F9-22** Dokončení: objednávka a rezervace skladu v jedné transakci
- `[PLÁN]` **F9-23** Děkovací stránka a veřejný odkaz na objednávku (ULID)
- `[PLÁN]` **F9-24** Bezpečnost: CSRF, validace, omezení rychlosti
- `[PLÁN]` **F9-25** Testy pokladny pro CZK i EUR a cs i en
- `[PLÁN]` **F9-26** Dokumentace modulů
- `[PLÁN]` **F9-27** Orders: `shop_order_adjustment` pro dopravu, poplatky a slevy a pravidlo znamének

### F10: Shipping, Payments, Mailing

_Rozhraní pro dopravu a platby, e-maily s Latte šablonami v repozitáři._ Hotovo 0 z 24 kroků.

- `[PLÁN]` **F10-01** Modul `Shipping`: struktura, `ShippingFacade`
- `[PLÁN]` **F10-02** Rozhraní `ShippingProvider` (cena, dostupnost, omezení)
- `[PLÁN]` **F10-03** Implementace: osobní odběr, výdejní místo, kurýr
- `[PLÁN]` **F10-04** Ceny po měnách a doprava zdarma od částky
- `[PLÁN]` **F10-05** Váha a rozměry z variant (příprava na vlnu 3)
- `[PLÁN]` **F10-06** Admin: správa způsobů dopravy a cen
- `[PLÁN]` **F10-07** Testy dopravy
- `[PLÁN]` **F10-08** Modul `Payments`: struktura, `PaymentsFacade`
- `[PLÁN]` **F10-09** Rozhraní `PaymentGateway` (zahájení, potvrzení, vrácení)
- `[PLÁN]` **F10-10** Implementace: bankovní převod
- `[PLÁN]` **F10-11** Implementace: dobírka
- `[PLÁN]` **F10-12** Implementace: karetní brána (výběr poskytovatele, např. GoPay nebo Comgate)
- `[PLÁN]` **F10-13** Webhooky: ověření podpisu, idempotence, logování
- `[PLÁN]` **F10-14** Kontrola, že se data karty nikdy neukládají
- `[PLÁN]` **F10-15** Stav platby a vazba na stav objednávky
- `[PLÁN]` **F10-16** Admin: správa platebních metod
- `[PLÁN]` **F10-17** Testy plateb (simulovaná brána)
- `[PLÁN]` **F10-18** Modul `Mailing`: odesílání, fronta, opakování
- `[PLÁN]` **F10-19** Latte šablony e-mailů v repozitáři; překládaný obsah oddělit od šablonovací logiky
- `[PLÁN]` **F10-20** E-maily: potvrzení objednávky, platba přijata, odesláno
- `[PLÁN]` **F10-21** Bezpečnost: escapování proměnných v šablonách
- `[PLÁN]` **F10-22** Testovací režim (zachytávání e-mailů ve vývoji)
- `[PLÁN]` **F10-23** Texty a testy
- `[PLÁN]` **F10-24** Dokumentace modulů

### F11: Front UI

_Veřejná část obchodu: `UI/Front`._ Hotovo 0 z 22 kroků.
- `[PLÁN]` **F11-01** Základní `@layout.latte`, Bootstrap, responzivita
- `[PLÁN]` **F11-02** CSP s nonce a bezpečnostní hlavičky pro front
- `[PLÁN]` **F11-03** Přepínač jazyka a měny se zachováním stavu
- `[PLÁN]` **F11-04** Domovská stránka- `[PLÁN]` **F11-05** Výpis kategorie s filtry (Naja)
- `[PLÁN]` **F11-06** Detail produktu: výběr variant, volby, cena, dostupnost
- `[PLÁN]` **F11-07** Košík a miniaturní košík
- `[PLÁN]` **F11-08** Pokladna po krocích- `[PLÁN]` **F11-09** Zákaznický účet: přihlášení, registrace, objednávky, adresy
- `[PLÁN]` **F11-10** Chybové stránky (404, 500)
- `[PLÁN]` **F11-11** Základní audit přístupnosti
- `[PLÁN]` **F11-12** SEO základ: title, meta, canonical, hreflang pro cs a en- `[PLÁN]` **F11-13** Sitemap a robots
- `[PLÁN]` **F11-14** Formuláře: jednotný vzhled a chybové hlášky přes překlady
- `[PLÁN]` **F11-15** Komponenty: karta produktu, cena, stav skladu, vzorník barev
- `[PLÁN]` **F11-16** Formátování cen a jednotek přes `intl`
- `[PLÁN]` **F11-17** Obrázky: responzivní a líné načítání
- `[PLÁN]` **F11-18** Výkon: cache šablon, komprese, hlavičky cache pro `assets`
- `[PLÁN]` **F11-19** Cache busting souborů z buildu
- `[PLÁN]` **F11-20** HTTP testy základních scénářů
- `[PLÁN]` **F11-21** End-to-end test základního nákupu (rozhodnout nástroj)
- `[PLÁN]` **F11-22** Dokumentace
- `[PLÁN]` **F11-23** Ověřit soulad zákaznického rozhraní s WCAG 2.1 AA

### F12: Admin UI

_Správa obchodu: `UI/Admin`._ Hotovo 0 z 27 kroků.

- `[PLÁN]` **F12-01** Layout, navigace a přihlášení administrace
- `[PLÁN]` **F12-02** Přísnější CSP pro administraci a oddělená cookie
- `[PLÁN]` **F12-03** Dashboard (základní čísla)
- `[PLÁN]` **F12-04** Datagrid: tenký adaptér a jednotný vzhled
- `[PLÁN]` **F12-05** Správa produktů: seznam, filtr, vytvoření, úprava
- `[PLÁN]` **F12-06** Správa variant a cen po měnách
- `[PLÁN]` **F12-07** Správa atributů a jednotek
- `[PLÁN]` **F12-08** Správa kategorií (strom)
- `[PLÁN]` **F12-09** Správa voleb zákazníka
- `[PLÁN]` **F12-10** Nahrávání obrázků
- `[PLÁN]` **F12-11** Správa skladu
- `[PLÁN]` **F12-12** Správa objednávek
- `[PLÁN]` **F12-13** Správa zákazníků
- `[PLÁN]` **F12-14** Správa dopravy a plateb
- `[PLÁN]` **F12-15** Správa jazyků a měn
- `[PLÁN]` **F12-16** Správa překladů (z F4)
- `[PLÁN]` **F12-17** Správa uživatelů a rolí
- `[PLÁN]` **F12-18** Nastavení obchodu (název, kontakt, výchozí jazyk a měna)
- `[PLÁN]` **F12-19** Šablony e-mailů
- `[PLÁN]` **F12-20** Auditní log: kdo co změnil
- `[PLÁN]` **F12-21** Hromadné akce a export seznamů
- `[PLÁN]` **F12-22** Administrace v češtině a angličtině
- `[PLÁN]` **F12-23** Potvrzení nebezpečných akcí (mazání)
- `[PLÁN]` **F12-24** CSRF u všech akcí
- `[PLÁN]` **F12-25** Test oprávnění: každý presenter má definovaná oprávnění
- `[PLÁN]` **F12-26** Dokumentace
- `[PLÁN]` **F12-27** Ověřit přístupnost administrace podle WCAG 2.1 AA (klávesnice, kontrast, popisky, chybové stavy, čtečky obrazovek)

### F13: Vyhledávání

_Rozhraní `ProductSearch` a jeho implementace._ Hotovo 0 z 10 kroků.

- `[PLÁN]` **F13-01** Rozhraní `ProductSearch`
- `[PLÁN]` **F13-02** Implementace nad MySQL FULLTEXT
- `[PLÁN]` **F13-03** Indexace názvů a popisů po jazycích
- `[PLÁN]` **F13-04** Filtry podle atributů ve vyhledávání
- `[PLÁN]` **F13-05** Řazení a stránkování
- `[PLÁN]` **F13-06** Našeptávač (Naja)
- `[PLÁN]` **F13-07** Test relevance na demo datech
- `[PLÁN]` **F13-08** Volitelná implementace Elasticsearch nebo OpenSearch (po rozhodnutí)
- `[PLÁN]` **F13-09** Synchronizace indexu přes události
- `[PLÁN]` **F13-10** Dokumentace

### F14: Bezpečnost a kvalita

_Průřezové kontroly před prvním nasazením._ Hotovo 0 z 25 kroků.

- `[PLÁN]` **F14-01** Bezpečnostní hlavičky: CSP s nonce, HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- `[PLÁN]` **F14-02** Test CSP: žádné `unsafe-inline`
- `[PLÁN]` **F14-03** Pravidlo v CI proti `noescape` v šablonách
- `[PLÁN]` **F14-04** Audit všech SQL dotazů (striktně přes repozitáře a parametry, prevence SQL injection)
- `[PLÁN]` **F14-05** Audit výstupů a administrátorských textů (prevence XSS, HTMLPurifier)
- `[PLÁN]` **F14-06** Kontrola CSRF napříč formuláři a signály
- `[PLÁN]` **F14-07** Omezení rychlosti: přihlášení, registrace, obnova hesla, pokladna
- `[PLÁN]` **F14-08** Audit nahrávání souborů
- `[PLÁN]` **F14-09** Zabezpečení session a cookies
- `[PLÁN]` **F14-10** Správa tajemství mimo Git a jejich rotace
- `[PLÁN]` **F14-11** Logování bez osobních údajů
- `[PLÁN]` **F14-12** Retence a mazání osobních údajů a logů; automatizace mazání podle retenčních pravidel F0-49
- `[PLÁN]` **F14-13** Záznamy o zpracování a zásady ochrany osobních údajů
- `[PLÁN]` **F14-14** Cookie lišta a kategorie cookies
- `[PLÁN]` **F14-15** `composer audit` a `npm audit` v CI
- `[PLÁN]` **F14-16** Kontrola licencí všech přímých i nepřímých závislostí a evidence povinností před každým vydáním
- `[PLÁN]` **F14-17** Nástroj pro analýzu kódu z hlediska bezpečnosti (rozhodnout)
- `[PLÁN]` **F14-18** Měření pokrytí testy a cíl
- `[PLÁN]` **F14-19** Architektonické testy v CI
- `[PLÁN]` **F14-20** Mutační testy klíčové domény (volitelně)
- `[PLÁN]` **F14-21** Zátěžový test základních stránek
- `[PLÁN]` **F14-22** Externí bezpečnostní audit před spuštěním
- `[PLÁN]` **F14-23** Plán reakce na incidenty včetně závažnosti, odpovědností a reakčních dob
- `[PLÁN]` **F14-24** Postup nahlášení zranitelnosti
- `[PLÁN]` **F14-25** Dokumentace bezpečnosti

### F15: Provoz a hostování

_Příprava pro produkční nasazení._ Hotovo 0 z 18 kroků.

- `[PLÁN]` **F15-01** Návrh infrastruktury pro produkci
- `[PLÁN]` **F15-02** Automatizovaný provisioning serveru
- `[PLÁN]` **F15-03** Konfigurace webového serveru (Nginx / Apache) a PHP-FPM
- `[PLÁN]` **F15-04** Nastavení SSL certifikátů (Let's Encrypt, auto-renewal)
- `[PLÁN]` **F15-05** Automatický deploy skript v CI/CD
- `[PLÁN]` **F15-06** Automatické spouštění migrací při deployi s pre-migration zálohou DB
- `[PLÁN]` **F15-07** Skript pro rychlý rollback a obnovu databáze ze snapshotu
- `[PLÁN]` **F15-08** Konfigurace cronu, plánovače a spouštění databázové fronty přes workery
- `[PLÁN]` **F15-09** Nastavení monitoringu dostupnosti s cílem 99,5 % měsíčně (uptime monitor)
- `[PLÁN]` **F15-10** Sledování výkonu aplikace (APM)
- `[PLÁN]` **F15-11** Nastavení centralizovaného logování
- `[PLÁN]` **F15-12** Denní plné zálohy DB, průběžné binlogy, rotace, šifrování a off-site uložení
- `[PLÁN]` **F15-13** Zálohování nahraných souborů; šifrované off-site uložení
- `[PLÁN]` **F15-14** Pravidelně testovat obnovu databáze i nahraných souborů ze záloh (disaster recovery)
- `[PLÁN]` **F15-15** Stavový endpoint aplikace (`/healthcheck`)- `[PLÁN]` **F15-16** Správa proměnných prostředí na produkci
- `[PLÁN]` **F15-17** Nastavení CDN pro statické assety (volitelně)
- `[PLÁN]` **F15-18** Provozní příručka (runbook pro správce)

### F16: Výuka
_Výukové materiály a lekce navazující na kód e-shopu._ Hotovo 0 z 36 kroků.

- `[K ÚPRAVĚ]` **F16-01** Lekce 1: Příprava prostředí a instalace (napsána, čeká na přejmenování a ověření)
- `[PLÁN]` **F16-02** Lekce 2: Struktura modulárního monolitu v Nette- `[PLÁN]` **F16-03** Lekce 3: Databázové schéma a Nextras migrace
- `[PLÁN]` **F16-04** Lekce 4: Nette Database Explorer bezpečně a přes repozitáře
- `[PLÁN]` **F16-05** Lekce 5: Vícejazyčnost a překladový subsystém v DB
- `[PLÁN]` **F16-06** Lekce 6: Katalog a řešení složitých variant (hashe)
- `[PLÁN]` **F16-07** Lekce 7: Výpočty cen, práce s měnami a daňové sazby- `[PLÁN]` **F16-08** Lekce 8: Správa skladu a rezervace s ošetřením souběhu
- `[PLÁN]` **F16-09** Lekce 9: Implementace košíku pro hosta i přihlášeného
- `[PLÁN]` **F16-10** Lekce 10: Autentizace, autorizace a správa rolí v Nette
- `[PLÁN]` **F16-11** Lekce 11: Objednávkový proces a stavový automat objednávky
- `[PLÁN]` **F16-12** Lekce 12: Integrace platebních bran a zpracování webhooků
- `[PLÁN]` **F16-13** Lekce 13: Napojení dopravců a generování štítků
- `[PLÁN]` **F16-14** Lekce 14: E-mailové notifikace se šablonami Latte v repozitáři
- `[PLÁN]` **F16-15** Lekce 15: Tvorba front-endu s Latte 3 a Naja
- `[PLÁN]` **F16-16** Lekce 16: Administrační rozhraní a tvorba datagridů
- `[PLÁN]` **F16-17** Lekce 17: Vyhledávání s MySQL FULLTEXT
- `[PLÁN]` **F16-18** Lekce 18: Bezpečnost Nette aplikace (CSP, escapování, rate limiting)
- `[PLÁN]` **F16-19** Lekce 19: Statická analýza PHPStan level max v praxi
- `[PLÁN]` **F16-20** Lekce 20: CI/CD a automatizované nasazování
- `[PLÁN]` **F16-21** Lekce 21: Architektura doménových událostí v monolitu
- `[PLÁN]` **F16-22** Lekce 22: Optimalizace výkonu a databázové indexy
- `[PLÁN]` **F16-23** Lekce 23: GDPR a ochrana osobních údajů v e-shopu
- `[PLÁN]` **F16-24** Lekce 24: Zákaznické volby a konfigurátory
- `[PLÁN]` **F16-25** Lekce 25: Řešení balíčků a množstevních slev
- `[PLÁN]` **F16-26** Lekce 26: Desetinná množství a metrové zboží
- `[PLÁN]` **F16-27** Lekce 27: Zálohy, licence a věková omezení
- `[PLÁN]` **F16-28** Lekce 28: Práce s PDF doklady a fakturací
- `[PLÁN]` **F16-29** Lekce 29: Exportní feedy a integrace se srovnávači
- `[PLÁN]` **F16-30** Lekce 30: Pokročilý refaktoring a údržba projektu
- `[PLÁN]` **F16-31** Cvičení: Rozšíření katalogu o nový atribut
- `[PLÁN]` **F16-32** Cvičení: Tvorba vlastní platební metody
- `[PLÁN]` **F16-33** Cvičení: Implementace slevového kupónu
- `[PLÁN]` **F16-34** Průvodce: Časté chyby začátečníků v Nette 3.2
- `[PLÁN]` **F16-35** Zkušební kvízy k jednotlivým modulům
- `[PLÁN]` **F16-36** Závěrečný certifikační projekt

### F17: Milník 1: první nasazení

_Finalizace prvního uceleného milníku se 3 demo produkty._ Hotovo 0 z 11 kroků.

- `[PLÁN]` **F17-01** Kompletní smoke test nákupního procesu pro demo produkt 1
- `[PLÁN]` **F17-02** Kompletní smoke test nákupního procesu pro demo produkt 2
- `[PLÁN]` **F17-03** Kompletní smoke test nákupního procesu pro demo produkt 3
- `[PLÁN]` **F17-04** Kontrola správnosti výpočtů DPH a zaokrouhlení na objednávce
- `[PLÁN]` **F17-05** Ověření kompletního toku v měnách CZK i EUR
- `[PLÁN]` **F17-06** Ověření vícejazyčnosti (přepnutí cs/en na všech stránkách M1)
- `[PLÁN]` **F17-07** Ověření administrace: správa produktů 1–3 a vyřízení objednávek
- `[PLÁN]` **F17-08** Spuštění bezpečnostního kontrolního seznamu F14
- `[PLÁN]` **F17-09** Nasazení na testovací/staging server podle F15
- `[PLÁN]` **F17-10** Zkouška ostrého nasazení na produkční infrastrukturu
- `[PLÁN]` **F17-11** Tag verze `v1.0.0-M1` v repozitáři

### F18: Vlna 2: ceny a sklad

_Rozšíření pro produkty 6 až 9 (dárková sada, množstevní ceny, předobjednávka a desetinná množství)._ Hotovo 0 z 18 kroků.

- `[PLÁN]` **F18-01** Rozšíření DB schématu pro produktové balíčky (sady)
- `[PLÁN]` **F18-02** Výpočet skladové dostupnosti a ceny sady z komponent
- `[PLÁN]` **F18-03** Odpis komponent sady při objednávce
- `[PLÁN]` **F18-04** Rozšíření DB pro množstevní slevy (stupňovité ceníky)
- `[PLÁN]` **F18-05** Integrace stupňovitých slev do `PricingFacade` a košíku
- `[PLÁN]` **F18-06** Rozšíření pro předobjednávky: příznak a datum naskladnění
- `[PLÁN]` **F18-07** Chování košíku a pokladny při objednání předobjednávkového zboží
- `[PLÁN]` **F18-08** Rozšíření pro neceločíselné jednotky (metry, kilogramy)
- `[PLÁN]` **F18-09** Úprava `Quantity` a validátorů na kroky množství
- `[PLÁN]` **F18-10** Front UI: výběr a zadávání neceločíselného množství
- `[PLÁN]` **F18-11** Admin: správa sad a jejich vazeb
- `[PLÁN]` **F18-12** Admin: správa množstevních slev
- `[PLÁN]` **F18-13** Admin: správa předobjednávek
- `[PLÁN]` **F18-14** Migrace a demo data pro produkty vlny 2 (produkty 6–9)
- `[PLÁN]` **F18-15** Testy výpočtů sad a kontroly skladů
- `[PLÁN]` **F18-16** Testy množstevních slev v CZK i EUR
- `[PLÁN]` **F18-17** Testy předobjednávkového toku
- `[PLÁN]` **F18-18** Dokumentace rozšíření vlny 2

### F19: Speciální scénáře (produkty 10 až 13)

_Speciální chování: dlažba, digitální licence, věkové omezení, vratné zálohy a dárkové poukazy. Tato fáze propojuje scénář z konce vlny 2 s produkty vln 3 a 4._ Hotovo 0 z 22 kroků.

- `[PLÁN]` **F19-01** Rozšíření pro kalkulaci plochy/balení (např. obklady, dlažba)
- `[PLÁN]` **F19-02** Přepočet požadované plochy na celá balení v košíku
- `[PLÁN]` **F19-03** Podpora elektronických licencí a generování klíčů
- `[PLÁN]` **F19-04** Bezpečné doručení licenčního klíče po zaplacení
- `[PLÁN]` **F19-05** Příznak věkového omezení (18+) na úrovni produktu
- `[PLÁN]` **F19-06** Ověření věku v pokladně a integrace s ověřovací službou
- `[PLÁN]` **F19-07** Rozšíření schématu pro vratné obaly a zálohy (lahve, přepravky)
- `[PLÁN]` **F19-08** Oddělená položka zálohy v košíku a na dokladu (bez DPH / dle legislativy)
- `[PLÁN]` **F19-09** Systém dárkových poukazů (kódy, zůstatky, expirace)
- `[PLÁN]` **F19-10** Uplatnění poukazu v pokladně a částečné čerpání
- `[PLÁN]` **F19-11** Generování PDF dárkového poukazu
- `[PLÁN]` **F19-12** Admin: správa licencí a fondů klíčů
- `[PLÁN]` **F19-13** Admin: správa sazeb záloh
- `[PLÁN]` **F19-14** Admin: správa a generování poukazů
- `[PLÁN]` **F19-15** Front UI: kalkulačka plochy na detailu dlažby
- `[PLÁN]` **F19-16** Front UI: zadání a validace dárkového poukazu v košíku
- `[PLÁN]` **F19-17** Migrace a demo data pro speciální scénáře (produkty 10–13)
- `[PLÁN]` **F19-18** Testy přepočtu plochy a balení
- `[PLÁN]` **F19-19** Testy generování a expirovaných poukazů
- `[PLÁN]` **F19-20** Testy účtování vratných záloh
- `[PLÁN]` **F19-21** Právní revize toku ověření věku
- `[PLÁN]` **F19-22** Dokumentace speciálních scénářů

### F20: Vlna 4: pokročilé konfigurace (produkty 14 a 15)

_Rozšíření pro konfigurovatelnou fotoknihu, bezpečné nahrávání tiskových podkladů a kávové předplatné. Dárkový poukaz (produkt 13) je řešen ve F19._ Hotovo 0 z 14 kroků.

- `[PLÁN]` **F20-01** Doménový model vícekrokového konfigurátoru
- `[PLÁN]` **F20-02** Pravidla závislostí a nekompatibility mezi komponentami
- `[PLÁN]` **F20-03** Dynamický přepočet ceny a dostupnosti v konfigurátoru
- `[PLÁN]` **F20-04** Rozšíření pro uživatelské nahrávání souborů u položky košíku
- `[PLÁN]` **F20-05** Validace formátů (PDF/TIFF), rozměrů a DPI nahrávaných dat- `[PLÁN]` **F20-06** Bezpečné ukládání zákaznických podkladů mimo webroot
- `[PLÁN]` **F20-07** Podpora předplatného a opakovaných plateb
- `[PLÁN]` **F20-08** Plánovač periodického strhávání plateb přes platební bránu
- `[PLÁN]` **F20-09** Front UI: interaktivní konfigurátor produktů
- `[PLÁN]` **F20-10** Front UI: drag & drop uploader souborů s náhledem
- `[PLÁN]` **F20-11** Admin: kontrola a stažení tiskových dat u objednávky- `[PLÁN]` **F20-12** Admin: správa konfigurátorů a stromů pravidel
- `[PLÁN]` **F20-13** Migrace a demo data pro produkty 14–15
- `[PLÁN]` **F20-14** Dokumentace rozšíření vlny 4

### F21: Funkce, na kterých obchodník stojí
_Doplňkové nástroje nezbytné pro reálný provoz obchodu._ Hotovo 0 z 21 kroků.

- `[PLÁN]` **F21-01** Export XML feedu pro Google Nákupy
- `[PLÁN]` **F21-02** Export XML feedu pro Heureku
- `[PLÁN]` **F21-03** Export XML feedu pro Zboží.cz
- `[PLÁN]` **F21-04** Měření konverzí a remarketingové kódy (Google, Seznam, Meta)- `[PLÁN]` **F21-05** Generování daňových dokladů (faktur) v PDF
- `[PLÁN]` **F21-06** Číselné řady faktur a zálohových faktur
- `[PLÁN]` **F21-07** Zasílání faktury v příloze e-mailu po zaplacení
- `[PLÁN]` **F21-08** Hromadný tisk štítků pro přepravce
- `[PLÁN]` **F21-09** Export objednávek do účetních systémů (Pohoda XML)
- `[PLÁN]` **F21-10** Modul slevových kupónů s pravidly uplatnění
- `[PLÁN]` **F21-11** Opuštěné košíky: e-mailové připomenutí po vypršení
- `[PLÁN]` **F21-12** Věrnostní systém: sbírání a uplatnění bodů
- `[PLÁN]` **F21-13** Hodnocení a recenze produktů zákazníky
- `[PLÁN]` **F21-14** Schvalování a moderace recenzí v administraci
- `[PLÁN]` **F21-15** Hlídací pes (notifikace při naskladnění vyprodané varianty)
- `[PLÁN]` **F21-16** Pokročilá analytika prodejů a tržeb na dashboardu
- `[PLÁN]` **F21-17** Export prodejních reportů do XLSX/CSV
- `[PLÁN]` **F21-18** Evidence vráceného zboží a dobropisy
- `[PLÁN]` **F21-19** Správa reklamací (RMA modul)
- `[PLÁN]` **F21-20** Základní CMS modul: tvorba statických stránek (kontakt, obchodní podmínky)
- `[PLÁN]` **F21-21** Dokumentace komerčních a provozních funkcí
- `[PLÁN]` **F21-22** Import produktů z CSV/XML: mapování polí, validace, náhled před potvrzením a chybový protokol podle F0-39

---

## 5. Historie revizí

### v2.51 (10. 11. 2026)
- Zpřesněn rozsah F1-32: místo samotného SPIKE mapování se připraví minimální funkční kostra Front a Admin včetně presenterů, Latte šablon a rout `/` a `/admin`. Ověření proběhne přes HTTP; obsah stránek zůstane pouze jednoduchý uvítací text bez aplikační logiky.
- F1-32 je rozpracován; souhrn je **99 hotovo**, **1 rozděláno**, **1 k úpravě**, **445 plán**, **1 zrušeno**.

### v2.50 (10. 11. 2026)
- Oprava předchozí revize: F2-01 nebyl dokončen; jeho stav zůstává PLÁN. F1-32 až F1-35 jsou stále otevřené a F1 proto není uzavřená. Celkový stav vrácen na **99 hotovo**, **0 rozděláno**, **1 k úpravě**, **446 plán**, **1 zrušeno**.

### v2.49 (10. 11. 2026)
- Poznámka k předchozí revizi: F2-01 byl chybně označen jako HOTOVO; opraveno ve v2.50.
- Souhrnný stav před opravou: **100 hotovo**, **0 rozděláno**, **1 k úpravě**, **445 plán**, **1 zrušeno**.

### v2.48 (10. 11. 2026)
- Dokončen F1-30: čistý klon v samostatné složce úspěšně prošel instalací Composer/npm, validací a audity, PHPStan level max, PHP-CS-Fixer, Deptrac a sestavením assetů. Po doplnění `.gitattributes` a ověření pravidel LF/CRLF zůstal `git status --short` prázdný.
- Přidán `.gitattributes` s jednotnými pravidly konců řádků: textové soubory LF, Windows `.bat`/`.cmd` CRLF. Změna byla ověřena v čistém kontrolním klonu.
- Souhrnný stav: **99 hotovo**, **0 rozděláno**, **1 k úpravě**, **446 plán**, **1 zrušeno**.

### v2.47 (9. 11. 2026)
- Dokončena první kontrolní vlna Dependabotu: PR #5 (Composer minor/patch) a #6 (Sass) i major aktualizace GitHub Actions v PR #2, #3 a #4 byly po úspěšném CI sloučeny přes Squash and merge.
- Aktualizace byly staženy do lokálního prostředí; `composer install`, `npm ci`, Composer validate/audit, PHPStan, PHP-CS-Fixer, Deptrac, `npm run build` a `npm audit` prošly bez chyb. Automatické testy zůstávají vynechány do připravení testovací sady.
- Souhrnný stav zůstává: **98 hotovo**, **0 rozděláno**, **1 k úpravě**, **447 plán**, **1 zrušeno**.

### v2.46 (9. 11. 2026)
- Dokončen F1-28: GitHub přijal `.github/dependabot.yml`; Dependabot vytvořil první aktualizační pull requesty pro Composer, npm a GitHub Actions. Ověřeno 5 otevřených PR.
- Aktualizován souhrnný stav: **98 hotovo**, **0 rozděláno**, **1 k úpravě**, **447 plán**, **1 zrušeno**.

### v2.45 (9. 11. 2026)
- Zahájen F1-28: vytvořen `.github/dependabot.yml` s týdenními kontrolami Composer, npm a GitHub Actions každé pondělí v 06:00 (Europe/Berlin). Minor/patch aktualizace se seskupují podle ekosystému; major aktualizace zůstávají oddělené. Čeká se na ověření konfigurace a první aktualizační pull requesty.
- Souhrnný stav: **97 hotovo**, **1 rozděláno**, **1 k úpravě**, **447 plán**, **1 zrušeno**.

### v2.44 (9. 11. 2026)
- Dokončen F1-27: GitHub Actions workflow `.github/workflows/npm.yml` úspěšně proběhl (push, běh č. 1). Ověření Node.js/npm, `npm ci`, build CSS/JavaScript i `npm audit` skončily úspěšně.
- Souhrnný stav: **97 hotovo**, **0 rozděláno**, **1 k úpravě**, **448 plán**, **1 zrušeno**.

### v2.43 (9. 11. 2026)
- Zahájen F1-27: vytvořen `.github/workflows/npm.yml` pro Node.js podle `.nvmrc`, instalaci přes `npm ci`, sestavení CSS/JavaScriptu a kontrolu `npm audit`. Workflow čeká na ověření běhu v GitHub Actions.
- Souhrnný stav: **96 hotovo**, **1 rozděláno**, **1 k úpravě**, **448 plán**, **1 zrušeno**.

### v2.42 (9. 11. 2026)
- Dokončen F1-26: GitHub Actions workflow Composer úspěšně proběhl (push, běh č. 1). Úspěšně prošly Composer validace, instalace závislostí, audit závislostí, PHPStan, PHP-CS-Fixer i Deptrac. Automatické testy nejsou zařazeny do CI, dokud nebude připravena testovací sada.
- Souhrnný stav: **96 hotovo**, **0 rozděláno**, **1 k úpravě**, **449 plán**, **1 zrušeno**.

### v2.41 (9. 11. 2026)
- Zahájen F1-26: vytvořen `.github/workflows/composer.yml` pro Composer validaci, audit závislostí, PHPStan level max, PHP-CS-Fixer a Deptrac. Workflow čeká na ověření běhu v GitHub Actions; automatické testy zatím nejsou zařazeny, protože testovací sada není připravena.
- Souhrnný stav: **95 hotovo**, **1 rozděláno**, **1 k úpravě**, **449 plán**, **1 zrušeno**.

### v2.40 (9. 11. 2026)
- Dokončen F1-25: `npm run build` úspěšně sestavil samostatné CSS a JS soubory; kontrola obou Latte layoutů potvrdila správné reference na `front.css`/`front.js` a `admin.css`/`admin.js`.
- Aktualizován souhrnný stav: **95 hotovo**, **0 rozděláno**, **1 k úpravě**, **450 plán**, **1 zrušeno**.

### v2.39 (9. 11. 2026)
- Dokončen F1-24: přidán `.nvmrc` s Node.js `26.13.0`; `package.json` deklaruje podporované rozsahy Node.js `>=26.13.0 <27` a npm `>=12.2.2 <13`. Obě verze byly ověřeny v lokálním prostředí.
- Aktualizován souhrnný stav: **94 hotovo**, **0 rozděláno**, **1 k úpravě**, **451 plán**, **1 zrušeno**.

### v2.38 (9. 11. 2026)
- Dokončeny F1-22 a F1-23: samostatné vstupy Front/Admin a build/watch skripty byly lokálně ověřeny příkazem `npm run build`; všechny čtyři výstupy (`front.css`, `admin.css`, `front.js`, `admin.js`) existují a mají nenulovou velikost. Sass vypisuje pouze deprecation warnings pocházející zejména z Bootstrapu, build však končí úspěšně.
- Aktualizován souhrnný stav: **93 hotovo**, **0 rozděláno**, **1 k úpravě**, **452 plán**, **1 zrušeno**.

### v2.37 (9. 11. 2026)
- Zahájen F1-23: `package.json` nově sestavuje samostatné CSS/JS výstupy pro Front a Admin a obsahuje odpovídající watch skripty. Odstraněny původní společné vstupy `app.scss` a `app.js`; čeká se na lokální build.
- `STRUKTURA.md` aktualizována o rozdělení vstupů, sdílené části a názvy výstupních souborů.

### v2.36 (9. 11. 2026)
- Zahájen F1-22: vytvořeny samostatné Sass a JavaScript vstupy pro Front/Admin se sdílenými základy; struktura popsána v `STRUKTURA.md`. Lokální ověření ještě čeká.

### v2.35 (9. 11. 2026)
- Dokončen F1-20: Composer skripty `test`, `cs` a `arch` byly ověřeny; `composer validate` je validní, architektonická analýza bez nálezů, PHP-CS-Fixer po formátování našel 0 souborů k opravě a `composer audit` nenašel bezpečnostní zranitelnosti. Vlastní skript `audit` byl odstraněn kvůli kolizi s vestavěným příkazem Composeru.
- Aktualizován souhrnný stav: **91 hotovo**, **0 rozděláno**, **1 k úpravě**, **454 plán**, **1 zrušeno**.

### v2.34 (9. 11. 2026)
- Zahájen F1-20: do `composer.json` přidány sjednocené skripty `test`, `cs`, `arch` a `audit`; zachovány stávající skripty. Čeká se na lokální ověření.
- Souhrnný stav: **90 hotovo**, **1 rozděláno**, **1 k úpravě**, **454 plán**, **1 zrušeno**.

### v2.33 (9. 11. 2026)
- Dokončen F1-39: přidán `CHANGELOG.md` s formátem založeným na Keep a Changelog, pravidly pro obchodnicky relevantní záznamy a návazností na SemVer a Git tagy. První produkční vydání zatím nebylo publikováno.
- Souhrnný stav: **90 hotovo**, **0 rozděláno**, **1 k úpravě**, **455 plán**, **1 zrušeno**.

### v2.32 (9. 11. 2026)
- Dokončen F1-38: Monolog 3 nakonfigurován jako centrální PSR-3 logger přes Nette DI; ověřeno sestavení `Monolog\\Logger`, zápis JSON záznamu a maskování citlivého kontextu na `[REDACTED]`.
- Logy se ukládají mimo veřejný adresář, s oprávněními `0600` a prozatímní rotací; běžný HTTP provoz bude ověřen až po implementaci aplikace. PHPStan level max a Deptrac zůstaly čisté.
- Souhrnný stav: **89 hotovo**, **0 rozděláno**, **1 k úpravě**, **456 plán**, **1 zrušeno**.

### v2.31 (9. 11. 2026)
- Dokončen F1-18: nainstalován a nakonfigurován Deptrac 4.7.2 pro kontrolu architektonických hranic modulů. Pozitivní i negativní test ověřil pravidla; závěrečná analýza skončila s 0 violations, 0 uncovered a 0 errors.
- Aktualizován souhrnný stav: **88 hotovo**, **0 rozděláno**, **1 k úpravě**, **457 plán**, **1 zrušeno**.

### v2.30 (9. 11. 2026)
- Dokončen F1-36: odstraněny nepoužívané závislosti Vite a `@nette/vite-plugin`; Sass + esbuild build úspěšný, kontrola závislostí prázdná a npm audit bez zranitelností.
- Zaznamenána upozornění Sass deprecation pocházející z Bootstrapu; neblokují build a budou řešena samostatně.
- Souhrn: **87 hotovo**, **0 rozděláno**, **1 k úpravě**, **458 plán**, **1 zrušeno**.

### v2.28 (9. 11. 2026)
- Dokončen a lokálně ověřen F3-16: `composer db:reset` bezpečně odstranil 47 objektů a úspěšně provedl všechny 3 migrace (`structures`, `basic-data`, `dummy-data`), bez odstranění samotné databáze či jejích oprávnění.
- Nette Database Explorer potvrdil 3 demo produkty, 2 jazyky a 2 měny; `composer phpstan` proběhl čistě. Automatické testy zatím v projektu nejsou, proto `composer tester` nebyl spuštěn.
- Souhrn: **85 hotovo**, **0 rozděláno**, **1 k úpravě**, **460 plán**, **1 zrušeno**.

### v2.29 (9. 11. 2026)
- Dokončen F1-37: odstraněna přímá závislost `nextras/orm` a `OrmModel.php`; zachovány Nextras DBAL/Migrations. Ověřeny Composer validace, platform requirements a PHPStan level max.
- Souhrn: **86 hotovo**, **0 rozděláno**, **1 k úpravě**, **459 plán**, **1 zrušeno**.

### v2.27 (9. 11. 2026)
- Připraven bezpečný příkaz `composer db:reset` s kontrolou názvu databáze a lokálního migračního účtu; reset zachovává databázi i její oprávnění. F3-16 zůstává ROZDĚLÁNO do ověření v lokálním prostředí.

### v2.26 (9. 11. 2026)
- Zpřesněny provozní kroky pro SLA 99,5 %, off-site šifrované zálohy DB i souborů, obnovu, workery, reakční doby incidentů a kontrolu licencí.
- Počet kroků a souhrnné stavy zůstávají beze změny: **84 hotovo**, **1 rozděláno**, **1 k úpravě**, **460 plán**, **1 zrušeno**.

### v2.25 (9. 11. 2026)
- Doplněny chybějící implementační kroky pro `CHANGELOG.md` (F0-28), import produktů CSV/XML (F0-39) a plnou přístupnost Front podle WCAG 2.1 AA (F0-48).
- Sjednocen plán e-mailových šablon s rozhodnutím ukládat Latte soubory do repozitáře; upřesněna implementace WebP/AVIF, 2FA a retence logů.
- Rozšířen souhrn o tři navazující úkoly: **84 hotovo**, **1 rozděláno**, **1 k úpravě**, **460 plán**, **1 zrušeno**.

### v2.24 (9. 11. 2026)
- Závěrečný audit sjednotil testovací scénáře katalogu s kanonickými 15 demo produkty; doplněny testy produktů 4 a 5.
- Opraveny rozsahy produktů ve fázích F18–F20 a zastaralé popisy e-mailových šablon a front-endového JavaScriptu.
- Doplněn implementační krok F1-38 pro Monolog podle schváleného F0-46.
- Souhrn po doplnění dvou testovacích kroků: **84 hotovo**, **1 rozděláno**, **1 k úpravě**, **457 plán**, **1 zrušeno**.

### v2.23 (9. 11. 2026)
- Dokončena závěrečná kontrola návazností: 46 tabulek v počáteční SQL migraci odpovídá dokumentaci DATABASE; sjednocen popis PHP 8.3+, oficiální název, databázové vrstvy a zbývající úklid ORM.
- Zpřesněn F0-54: audit PHP/NEON souborů nalezl jedinou zbývající referenci na Nextras ORM v `OrmModel.php`; odstranění závislosti a souboru je samostatný úkol F1-37.- Souhrn ROADMAP zůstává: **84 hotovo**, **1 rozděláno**, **1 k úpravě**, **454 plán**, **1 zrušeno**.

### v2.22 (9. 11. 2026)- Proveden audit kanonické dokumentace proti skutečnému stromu a konfiguraci repozitáře. Opraveny verze, odkazy, struktura kapitol a zastaralé souhrny.
- F0 je uzavřena: **54/54 HOTOVO**.
- Zpřesněny ověřené kroky F1-17, F1-19, F1-21, F1-29 a F1-31; přidány F1-37 (úklid ORM) a F12-27 (přístupnost administrace).- Aktuální souhrn: **84 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 rozhodnout**, **0 blokováno**, **454 plán**, **1 zrušeno**.

### v2.21 (9. 11. 2026)- Dokončeno F0-54: Nette Database Explorer bude jedinou databázovou vrstvou aplikačního kódu; Nextras ORM a jeho `OrmModel.php` budou po ověření závislostí odstraněny, Nextras Migrations/DBAL mohou zůstat pro migrace a testy.
- Aktualizován souhrnný stav: **79 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.20 (9. 11. 2026)
- Dokončeno F0-53: stanoveno bezpečné oddělení demo dat a testovacích operací od skutečného provozu včetně ochrany produkčního prostředí.
- Aktualizován souhrnný stav: **72 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.19 (9. 11. 2026)
- Dokončeno F0-50: zavedena kontrola licencí přímých i nepřímých závislostí, evidence licenčních povinností a kontrola před každým vydáním.
- Aktualizován souhrnný stav: **71 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.18 (9. 11. 2026)
- Dokončeno F0-49: stanovena pravidla pro zákonné uchovávání objednávek a dokladů, GDPR odstranění/anonymizaci údajů, retenci logů a ochranu auditních záznamů.
- Aktualizován souhrnný stav: **70 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.17 (9. 11. 2026)
- Dokončeno F0-48: zákaznické rozhraní i administrace budou cílit na WCAG 2.1 AA; požadavky se budou ověřovat průběžně a před vydáním.
- Aktualizován souhrnný stav: **69 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.16 (9. 11. 2026)
- Dokončeno F0-47: během současného vývoje zůstáváme přímo ve větvi `main` s lokální zálohou; po prvním vydání přejdeme na stabilní `main`, vývojové větve, Pull Requesty a Git tagy podle SemVer.
- Aktualizován souhrnný stav: **68 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.15 (9. 11. 2026)
- Dokončeno F0-46: Tracy pro vývojovou diagnostiku, Monolog pro aplikační logování, strukturované logy a ochrana citlivých údajů; Sentry případně později.
- Aktualizován souhrnný stav: **67 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.14 (9. 11. 2026)
- Dokončeno F0-45: projekt nebude podporovat instalovatelné pluginy třetích stran; vlastní funkce zůstávají v jádře a interních modulech, externí služby se napojují přes interní adaptéry.
- Aktualizován souhrnný stav: **66 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.13 (9. 11. 2026)
- Dokončeno F0-43: oficiální název projektu je **ArgoMi-BestShop**; databáze zůstává `argomi_bestshop` a technický název repozitáře/projektové cesty `argomi-bestshop` se nemění.
- Aktualizován souhrnný stav: **65 hotovo**, **1 rozděláno**, **1 k úpravě**, **0 čeká na rozhodnutí**.

### v2.12 (8. 11. 2026)
- Dokončeno F0-17: připraví se návrh smlouvy o zpracování osobních údajů (DPA) a GDPR dokumentace pro hostované obchody; finální znění a retenční pravidla před spuštěním zkontroluje právník a specialista na ochranu osobních údajů.
- Aktualizován souhrnný stav: **64 hotovo**, **1 rozděláno**, **2 k úpravě**, **0 čeká na rozhodnutí**.


### v2.11 (8. 11. 2026)
- Dokončeno F0-16: zachován režim „všechna práva vyhrazena“; připraví se návrh studentské licence a podmínek služby a finální znění před distribucí nebo komerčním spuštěním zkontroluje právník.
- Aktualizován souhrnný stav: **63 hotovo**, **1 rozděláno**, **2 k úpravě**, **1 čeká na rozhodnutí**.


### v2.10 (8. 11. 2026)
- Dokončeno F0-32: e-mailové šablony budou uloženy jako Latte soubory v repozitáři a verzovány společně s aplikací; zdrojové šablony se nebudou upravovat v administraci.
- Aktualizován souhrnný stav: **62 hotovo**, **1 rozděláno**, **2 k úpravě**, **2 čeká na rozhodnutí**.


### v2.9 (8. 11. 2026)
- Dokončeno F0-31: úlohy se ukládají do databázové fronty a zpracovává je worker spouštěný cronem. Architektura umožní pozdější provoz samostatných či více souběžných workerů.
- Aktualizován souhrnný stav: **61 hotovo**, **1 rozděláno**, **2 k úpravě**, **3 čeká na rozhodnutí**.

### v2.8 (8. 11. 2026)
- Dokončeno F0-30: originály obrázků se zachovávají; miniatury se generují primárně ve WebP, AVIF je doplňková optimalizovaná varianta. Velikosti a kvalita jsou centrálně konfigurovatelné; řešení podporuje ořez, poměr stran a responzivní zobrazování.
- Aktualizován souhrnný stav: **60 hotovo**, **1 rozděláno**, **2 k úpravě**, **4 čeká na rozhodnutí**.

### v2.7 (8. 11. 2026)
- Dokončeno F0-29: pro generování PDF byla zvolena knihovna mPDF. Vystavené doklady vycházejí z neměnného snímku objednávky a uchovávají se beze změn.
- Aktualizován souhrnný stav: **59 hotovo**, **1 rozděláno**, **2 k úpravě**, **5 čeká na rozhodnutí**.

### v2.6 (8. 11. 2026)
- Dokončeno F0-28: zvoleno verzování platformy podle SemVer, Git tagy produkčních vydání, `CHANGELOG.md` a srozumitelný přehled změn pro provozovatele obchodů. Verze označuje platformu, nikoliv jednotlivé obchody; databázové změny probíhají dopřednými migracemi.
- Aktualizován souhrnný stav: **58 hotovo**, **1 rozděláno**, **2 k úpravě**, **6 čeká na rozhodnutí**.

### v2.5 (8. 11. 2026)
- Dokončeno F0-27: obsahové texty se ukládají jako čistý Markdown; při vykreslení se převedou na HTML a výsledné HTML se vyčistí přes HTMLPurifier nebo schválený ekvivalent.
- Aktualizován souhrnný stav: **57 hotovo**, **1 rozděláno**, **2 k úpravě**, **7 čeká na rozhodnutí**.

### v2.4 (8. 11. 2026)
- Dokončeno F0-26: pro administrační tabulky byl zvolen Ublaboo Datagrid. Implementace ani konfigurace zatím neproběhla; knihovna bude zapouzdřena vlastní aplikační vrstvou.
- Aktualizován souhrnný stav: **56 hotovo**, **1 rozděláno**, **2 k úpravě**, **8 čeká na rozhodnutí**.

### v2.3 (8. 11. 2026)
- Dokončeno F0-24: doručovací integrace bude používat `ShippingProvider` a samostatné adaptéry; architektura umožní současné použití více dopravců.
- Aktualizován souhrnný stav: **55 hotovo**, **1 rozděláno**, **2 k úpravě**, **9 čeká na rozhodnutí**.

### v2.2 (8. 11. 2026)
- Dokončeno F0-23: platební integrace bude používat vlastní rozhraní `PaymentProvider`; konkrétního poskytovatele vybereme až před produkčním nasazením.
- Aktualizován souhrnný stav: **54 hotovo**, **1 rozděláno**, **2 k úpravě**, **10 čeká na rozhodnutí**.

### v2.1 (8. 11. 2026)
- Dokončeno F0-22: první verze je B2C, ale datový a doménový model počítá s firemními zákazníky a IČO/DIČ; ověřování ARES/VIES a pokročilé B2B funkce se odkládají.
- Aktualizován souhrnný stav: **53 hotovo**, **1 rozděláno**, **2 k úpravě**, **11 čeká na rozhodnutí**.

### v2.0 (8. 11. 2026)
- Dokončeno F0-21: všechny jazykové varianty URL mají explicitní prefix, včetně výchozího jazyka (`/cs`, `/en`).
- Aktualizován souhrnný stav: **52 hotovo**, **1 rozděláno**, **2 k úpravě**, **12 čeká na rozhodnutí**.

### v1.9 (8. 11. 2026)
- Dokončen F1-16: nainstalován PHP-CS-Fixer 3.95.29, nakonfigurován pevný standard `@PER-CS3.0` v `.php-cs-fixer.dist.php` a automaticky naformátováno všech 6 existujících PHP souborů.
- Opakovaná kontrola PHP-CS-Fixer potvrdila **0 souborů k opravě**; PHPStan `level: max` zůstal bez chyb. Automatické testy zatím nejsou součástí projektu a nebyly spouštěny.
- Commit `d8ff580` obsahuje konfiguraci nástroje, Composer závislost a formátovací změny.
- Aktualizován souhrnný stav: **48 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v1.8 (8. 11. 2026)
- Schválen a do ZAMER zapsán standard PHP kódu **PER Coding Style 3.0 (PER-CS 3.0)**.
- Zahájen F1-16: zbývá nastavit a ověřit nástroj pro kontrolu/formátování kódu.
- Aktualizován souhrnný stav: **47 hotovo**, **2 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v1.7 (8. 11. 2026)
- Dokončeny F1-14 a F1-15: konfigurace PHPStan na `level: max` a oprava všech nálezů v kostře bez baseline.
- Ověřeno čisté dokončení `composer phpstan`.
- Aktualizován souhrnný stav: **47 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v1.6 (8. 11. 2026)
- Dokončen F1-13: ověřeny přímé vývojové závislosti PHPStan a všechna rozšíření; doplněny a nainstalovány `phpstan/phpstan-deprecation-rules` 2.0.5.
- Composer potvrdil automatickou registraci Nette, strict a deprecation pravidel přes `extension-installer`; instalace proběhla bez bezpečnostních upozornění.
- Aktualizován souhrnný stav: **45 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.
### v1.5 (8. 11. 2026)
- Dokončen F1-12: ověřena přímá závislost `nette/database` ve verzi 3.2.10; shodná verze je nainstalována i uzamčena v `composer.lock`.
- Aktualizován souhrnný stav: **44 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.
### v1.4 (8. 11. 2026)
- Dokončen F1-11: vytvořen lokální `config/local.neon` s ovladačem `pdo_mysql` a účtem `argomi_app`; ověřeno sestavení Nette DI kontejneru přes `Bootstrap.php`.
- Samotné sestavení kontejneru nepotvrzuje navázání databázového spojení; jeho ověření zůstává na pozdější krok.
- Aktualizován souhrnný stav: **43 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.
### v1.3 (8. 11. 2026)
- Dokončen F1-10: ověřena existující databáze `argomi_bestshop` s `utf8mb4` / `utf8mb4_0900_ai_ci`; zachováno všech 47 existujících tabulek.
- Vytvořeny účty `argomi_app` a `argomi_migrator` s hesly generovanými MySQL. Aplikační účet má pouze SELECT/INSERT/UPDATE/DELETE; migrační účet má navíc CREATE/ALTER/DROP/INDEX/REFERENCES. Oba účty jsou omezené na `argomi_bestshop.*` a jejich přihlášení bylo ověřeno.- Dokončen také F3-21: oddělení databázových účtů aplikace a migrací.
- Aktualizován souhrnný stav: **42 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v1.2 (8. 11. 2026)
- Dokončen F1-09: minimální verze PHP projektu zvýšena na 8.3; nastaven `config.platform.php=8.3.0` a `sort-packages=true`; zachovány existující Composer skripty `phpstan` a `tester`.
- `composer update --lock` synchronizoval metadata `composer.lock` bez změny verzí závislostí. Ověřeno `composer validate --no-check-publish` a `composer check-platform-reqs` na PHP 8.5.12.
- Commit `09c7469` nahrán na GitHub; aktualizován souhrnný stav: **40 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v1.1 (8. 11. 2026)
- Dokončen F1-08: ověřena konfigurace PHP 8.5.12 v CLI i Apache (`apache2handler`); oba používají stejný `php.ini`. Potvrzeny `memory_limit=512M`, `error_reporting=E_ALL`, `display_errors=On`, `display_startup_errors=On` a `log_errors=On`; konfigurace nevyžaduje změny.
- Aktualizován souhrnný stav: **39 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.9 (8. 11. 2026)
- Dokončen F1-07: vytvořen a ověřen kořenový `.editorconfig` (UTF-8, LF, koncový nový řádek, mezery; 4 mezery pro výchozí soubory, 2 pro konfigurace a frontend, CRLF pro `.bat`/`.cmd`).
- Aktualizován souhrnný stav: **38 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.8 (8. 11. 2026)
- Dokončen F1-06: ověřena pravidla `.gitignore` pro `vendor/`, `node_modules/`, `temp/`, `log/`, `config/local.neon` a `www/assets/`; Git tyto cesty nesleduje a všechna pravidla se správně uplatňují.
- Aktualizován souhrnný stav: **37 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.7 (8. 11. 2026)
- Dokončen F1-04: ověřena konfigurace Apache virtuálního hostu; HTTP i HTTPS používají `C:/laragon/www/argomi-bestshop/www` jako `DocumentRoot`.
- Aktualizován souhrnný stav: **36 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.6 (8. 11. 2026)
- Dokončen F1-03: v Laragonu povoleno a ověřeno všech 8 požadovaných PHP rozšíření v aktivním PHP CLI.
- Aktualizován souhrnný stav: **35 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.5 (8. 11. 2026)
- Dokončen F1-02: ověřeny a aktualizovány nástroje vývojového prostředí.
- Ověřené verze: PHP 8.5.12, Composer 2.11.1, Node.js 26.13.0, npm 12.2.2, MySQL 8.4.13 a Git for Windows 2.56.0.windows.4.
- Ověřen Composer platform check; `npm ci` a `npm run build` proběhly úspěšně bez zranitelností v npm auditu.
- MySQL 8.4.13 úspěšně spustil obnovený datový adresář; databáze `argomi_bestshop` obsahuje 47 tabulek.
- GitHub připojení ověřeno příkazem `git ls-remote origin HEAD`; pracovní větev `main` je synchronizovaná s `origin/main`.
- Aktualizován souhrnný stav: **34 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.4 (8. 11. 2026)
- Dokončen F1-01: úklid výchozí Nette kostry a sjednocení metadat projektu.
- Ověřeno `composer validate --no-check-publish`, `npm ci` a `npm run build`; metadata Composer lock synchronizována bez změny verzí závislostí.
- Aktualizován souhrnný stav: **33 hotovo**, **1 rozděláno**, **2 k úpravě**, **16 čeká na rozhodnutí**.

### v0.3 (8. 11. 2026)
- Dokončena a integrována Nextras Migrations v Nette DI (`common.neon`) (F3-02).
- Zprovozněny a ověřeny migrační skupiny: `structures`, `basic-data`, `dummy-data`.
- Zprovozněn konzolový příkaz přes Contributte Console `php bin/console migrations:continue` (F3-18).
- Schváleno rozhodnutí ponechat monolitický DDL soubor `2026-11-04-200000-init.sql` pro Vlnu 1 s inkrementálními přírůstky do budoucna (F3-05).
- Uzavřena 3 klíčová architektonická rozhodnutí:
  - **F3-26:** Databázový přístup určen přes čistý **Nette Database Explorer** s dotazy striktně v repozitářích přes DI a návratem typovaných readonly DTO (žádné dotazy v presenterech, ActiveRow nepřekračuje repozitář).
  - **F3-27:** Spouštění migrací na produkci určeno **automaticky v CI/CD pipeline / deploy skriptu přes CLI**.
  - **F3-28:** Rollback strategie určena **výhradně přes pre-migration dump/snapshot DB** a obnovením ze zálohy v případě chyby.
- Zahájena příprava zjednodušeného Composer příkazu pro vývojový reset databáze `composer db:reset` (F3-16).
- Aktualizována souhrnná statistika: **25 hotovo**, **2 rozděláno**, **18 čeká na rozhodnutí**.

### v0.2 (4. 11. 2026)
- Převzetí a ověření schématu v1 (`2026-11-04-200000-init.sql`), základních dat (`2026-11-04-201000-basic-data.sql`) a demo dat (`2026-11-04-202000-demo-products.sql`).
- Upřesnění dekompozice na Vlny 1 až 4.

### v0.1 (říjen 2026)
- Výchozí návrh a rozpad na 528 kroků a 22 fází.