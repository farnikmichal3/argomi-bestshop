# ArgoMi-bestshop – plán vývoje (roadmap)

Verze dokumentu 0.1 · 4. 11. 2026 · navazuje na `ArgoMi-bestshop-ZAMER.md` a `ArgoMi-bestshop-STRUKTURA.md`

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

1. Kód odpovídá ADR a zásadám projektu (`declare(strict_types=1)`, striktní DI, žádné statické přístupy).
2. PHPStan level max bez výjimek a bez baseline.
3. Testy (jednotkové nebo integrační) a architektonické testy procházejí v CI.
4. Texty a formáty jsou v databázi v obou jazycích (žádné napevno zapsané řetězce, měny a čísla).
5. Provedena bezpečnostní kontrola: vstup, výstup, oprávnění.
6. Dokumentace: README modulu, případně ADR.
7. Pro demo produkty jsou k dispozici demo data a test.

### Milníky

| Milník | Obsah |
|---|---|
| **M1: první nasazení** | F0 až F12, F14, F15, F17; z F13 jen základ nad MySQL FULLTEXT; demo produkty 1 až 3 |
| **M2** | F18 (vlna 2: sada, množstevní slevy, předobjednávka, neceločíselné množství) |
| **M3** | F19 (vlna 3: dlaždice, licence, věkové omezení, záloha, poukaz) |
| **M4** | F20 (vlna 4: konfigurátor, nahrání souboru, předplatné) |
| **M5** | F21 (funkce, na kterých obchodník stojí; průběžně) |
| **Průběžně** | F16 (výuka) navazuje na hotové fáze |

## 2. Přehled stavu

Celkem **511 kroků**: hotovo 11 (2 %), rozdělaných 5, k úpravě 4, čeká na rozhodnutí 17, naplánováno 474.

| Fáze | Název | Kroků | Hotovo | Rozděl. | K úpravě | Rozhodnout | Plán |
|---|---|---:|---:|---:|---:|---:|---:|
| F0 | Rozhodnutí a dokumentace | 45 | 11 | 3 | 3 | 17 | 11 |
| F1 | Prostředí a repozitář | 35 | 0 | 1 | 0 | 0 | 34 |
| F2 | Sdílené jádro (Shared) | 16 | 0 | 0 | 0 | 0 | 16 |
| F3 | Databáze a migrace | 22 | 0 | 1 | 0 | 0 | 21 |
| F4 | Překlady v databázi | 25 | 0 | 0 | 0 | 0 | 25 |
| F5 | Catalog (vlna 1) | 34 | 0 | 0 | 0 | 0 | 34 |
| F6 | Tax, Pricing, Inventory | 25 | 0 | 0 | 0 | 0 | 25 |
| F7 | Cart | 16 | 0 | 0 | 0 | 0 | 16 |
| F8 | Zákazníci a administrátoři | 21 | 0 | 0 | 0 | 0 | 21 |
| F9 | Orders a Checkout | 26 | 0 | 0 | 0 | 0 | 26 |
| F10 | Shipping, Payments, Mailing | 24 | 0 | 0 | 0 | 0 | 24 |
| F11 | Front UI | 22 | 0 | 0 | 0 | 0 | 22 |
| F12 | Admin UI | 26 | 0 | 0 | 0 | 0 | 26 |
| F13 | Vyhledávání | 10 | 0 | 0 | 0 | 0 | 10 |
| F14 | Bezpečnost a kvalita | 25 | 0 | 0 | 0 | 0 | 25 |
| F15 | Provoz a hostování | 18 | 0 | 0 | 0 | 0 | 18 |
| F16 | Výuka | 35 | 0 | 0 | 1 | 0 | 34 |
| F17 | Milník 1: první nasazení | 11 | 0 | 0 | 0 | 0 | 11 |
| F18 | Vlna 2: ceny a sklad | 18 | 0 | 0 | 0 | 0 | 18 |
| F19 | Vlna 3: speciální chování | 22 | 0 | 0 | 0 | 0 | 22 |
| F20 | Vlna 4: nejnáročnější | 14 | 0 | 0 | 0 | 0 | 14 |
| F21 | Funkce, na kterých obchodník stojí | 21 | 0 | 0 | 0 | 0 | 21 |

## 3. Nejbližší kroky

- `[ROZDĚLÁNO]` **F0-12** Průběžně aktualizovat dokument záměru po každém rozhodnutí
- `[ROZDĚLÁNO]` **F0-13** Roadmap (tento soubor): první verze hotová, čeká na tvou revizi
- `[ROZDĚLÁNO]` **F0-14** Návrh struktury projektu (STRUKTURA): první verze hotová, čeká na tvou revizi
- `[K ÚPRAVĚ]` **F0-43** Lekce 1: přejmenovat projekt na `argomi-bestshop` a databázi na `argomi_bestshop`
- `[K ÚPRAVĚ]` **F0-44** ZAMER: promítnout schválenou strukturu `app/` do kapitoly 3.2
- `[K ÚPRAVĚ]` **F0-45** ZAMER: doplnit odkazy na ROADMAP a STRUKTURA
- `[ROZDĚLÁNO]` **F1-01** Založit projekt z `nette/web-project` (zakládá autor projektu)
- `[ROZDĚLÁNO]` **F3-01** Schéma vlny 1 (`001`), základní data (`002`) a demo data (`003`) jsou napsané a čekají na první spuštění v MySQL
- `[K ÚPRAVĚ]` **F16-01** Lekce 1: Příprava prostředí a instalace (napsána, čeká na přejmenování a ověření)
- `[ROZHODNOUT]` **F0-15** Schválit strukturu `app/` (UI + Modules, viz STRUKTURA)
- `[PLÁN]` **F1-02** Ověřit verze PHP, Composeru, Node.js, MySQL a Gitu (kontrola z Lekce 1)
- `[PLÁN]` **F1-03** Povolit potřebná PHP rozšíření (`pdo_mysql`, `mbstring`, `intl`, `openssl`, `curl`, `fileinfo`, `zip`, `gd`)
- `[PLÁN]` **F1-04** Nastavit document root na `www/` ve virtuálním hostu
- `[PLÁN]` **F1-05** Založit soukromý repozitář a první commit
- `[PLÁN]` **F1-06** Doplnit `.gitignore` (vendor, node_modules, temp, log, local.neon, www/assets)
- `[PLÁN]` **F1-07** Přidat `.editorconfig`

## 4. Plán po fázích

### F0: Rozhodnutí a dokumentace

_Co je rozhodnuto, co čeká na rozhodnutí, a dokumenty, ke kterým se budeme vracet._ Hotovo 11 z 45 kroků.

- `[HOTOVO]` **F0-01** Zapsat vizi, hodnoty a pořadí priorit (ZAMER kap. 1)
- `[HOTOVO]` **F0-02** Rozhodnout distribuci: zdarma hostovaná služba a studentská licence, ne open source (kap. 2)
- `[HOTOVO]` **F0-03** Rozhodnout architekturu: modulární monolit, jedna instance a jedna databáze na obchod (kap. 3)
- `[HOTOVO]` **F0-04** Zvolit technologie a pravidla pro přijímání závislostí (kap. 4)
- `[HOTOVO]` **F0-05** Zapsat bezpečnostní zásady (kap. 5)
- `[HOTOVO]` **F0-06** Rozhodnout zásady databáze: Explorer, MySQL, migrace, atributy, ID, překlady a měny (kap. 6)
- `[HOTOVO]` **F0-07** Sestavit 15 demo produktů a rozdělit je do čtyř vln (kap. 7)
- `[HOTOVO]` **F0-08** Rozhodnout demo data ve dvou jazycích (cs, en) a dvou měnách (CZK, EUR) (kap. 8)
- `[HOTOVO]` **F0-09** Rozhodnout model překladů v databázi: obsah entit, jednotky, systémové texty, přepsání administrátorem
- `[HOTOVO]` **F0-10** Ověřit přístup AI k repozitáři na GitHubu
- `[HOTOVO]` **F0-11** Opravit název projektu na ArgoMi-bestshop v dokumentu záměru
- `[ROZDĚLÁNO]` **F0-12** Průběžně aktualizovat dokument záměru po každém rozhodnutí
- `[ROZDĚLÁNO]` **F0-13** Roadmap (tento soubor): první verze hotová, čeká na tvou revizi
- `[ROZDĚLÁNO]` **F0-14** Návrh struktury projektu (STRUKTURA): první verze hotová, čeká na tvou revizi
- `[ROZHODNOUT]` **F0-15** Schválit strukturu `app/` (UI + Modules, viz STRUKTURA)
- `[ROZHODNOUT]` **F0-16** Licence a podmínky služby, studentská licence (právník)
- `[ROZHODNOUT]` **F0-17** Smlouva o zpracování osobních údajů a GDPR pro hostované obchody (právník)
- `[ROZHODNOUT]` **F0-18** Vyhledávání: MySQL FULLTEXT jako výchozí, Elasticsearch/OpenSearch volitelně
- `[ROZHODNOUT]` **F0-19** Vývojové prostředí mimo Windows (Docker nebo DDEV)
- `[ROZHODNOUT]` **F0-20** Ceny v databázi s DPH, nebo bez (výchozí hodnota `tax_included`)
- `[ROZHODNOUT]` **F0-21** Jazyk v URL (`/cs`, `/en`) a prefix výchozího jazyka
- `[ROZHODNOUT]` **F0-22** Role v administraci, oprávnění k úpravě překladů, historie změn textů
- `[ROZHODNOUT]` **F0-23** Rozsah prvního milníku (nasaditelný obchod pro reálného zákazníka)
- `[ROZHODNOUT]` **F0-24** Nástroj pro hromadnou správu mnoha instancí
- `[ROZHODNOUT]` **F0-25** DPH v demo datech: jen české sazby, nebo i další země
- `[ROZHODNOUT]` **F0-26** Pravidlo zaokrouhlování částek
- `[ROZHODNOUT]` **F0-27** Konzolový nástroj (`bin/console`): contributte/console, nebo symfony/console
- `[ROZHODNOUT]` **F0-28** Nástroj pro testy: Nette Tester, nebo PHPUnit
- `[ROZHODNOUT]` **F0-29** Nástroj pro hlídání hranic modulů: deptrac, nebo phpat
- `[ROZHODNOUT]` **F0-30** Coding standard: nástroj a pravidla
- `[ROZHODNOUT]` **F0-31** Poskytovatel odesílání e-mailů (SMTP, knihovna)
- `[PLÁN]` **F0-32** Sepsat seznam funkcí navíc oproti PrestaShopu (zhruba 123) a rozdělit na nutné / brzy / někdy
- `[PLÁN]` **F0-33** ADR-0001: Modulární monolit
- `[PLÁN]` **F0-34** ADR-0002: Jedna instance a databáze na obchod
- `[PLÁN]` **F0-35** ADR-0003: Nette Database Explorer, `ActiveRow` jen uvnitř repozitářů
- `[PLÁN]` **F0-36** ADR-0004: Peníze jako celá čísla v nejmenších jednotkách, množství jako decimal
- `[PLÁN]` **F0-37** ADR-0005: Objednávka je snímek okamžiku nákupu
- `[PLÁN]` **F0-38** ADR-0006: Překlady v databázi s přepsáním administrátorem
- `[PLÁN]` **F0-39** ADR-0007: Struktura `app/` (UI a Modules)
- `[PLÁN]` **F0-40** ADR-0008: Front-end build bez Vite
- `[PLÁN]` **F0-41** ADR-0009: Pravidla pro přijímání závislostí
- `[PLÁN]` **F0-42** ADR-0010: Odkazy mezi moduly jen přes ID, bez cizích klíčů
- `[K ÚPRAVĚ]` **F0-43** Lekce 1: přejmenovat projekt na `argomi-bestshop` a databázi na `argomi_bestshop`
- `[K ÚPRAVĚ]` **F0-44** ZAMER: promítnout schválenou strukturu `app/` do kapitoly 3.2
- `[K ÚPRAVĚ]` **F0-45** ZAMER: doplnit odkazy na ROADMAP a STRUKTURA

### F1: Prostředí a repozitář

_Prázdný projekt, který jde po naklonování rozjet na pár příkazů a od prvního commitu hlídá kvalitu._ Hotovo 0 z 35 kroků.

- `[ROZDĚLÁNO]` **F1-01** Založit projekt z `nette/web-project` (zakládá autor projektu)
- `[PLÁN]` **F1-02** Ověřit verze PHP, Composeru, Node.js, MySQL a Gitu (kontrola z Lekce 1)
- `[PLÁN]` **F1-03** Povolit potřebná PHP rozšíření (`pdo_mysql`, `mbstring`, `intl`, `openssl`, `curl`, `fileinfo`, `zip`, `gd`)
- `[PLÁN]` **F1-04** Nastavit document root na `www/` ve virtuálním hostu
- `[PLÁN]` **F1-05** Založit soukromý repozitář a první commit
- `[PLÁN]` **F1-06** Doplnit `.gitignore` (vendor, node_modules, temp, log, local.neon, www/assets)
- `[PLÁN]` **F1-07** Přidat `.editorconfig`
- `[PLÁN]` **F1-08** Přidat `.gitattributes` (konce řádků)
- `[PLÁN]` **F1-09** `composer.json`: `platform.php`, `sort-packages`, skripty
- `[PLÁN]` **F1-10** Vytvořit databázi `argomi_bestshop` s `utf8mb4` a vlastního uživatele s nejmenšími oprávněními
- `[PLÁN]` **F1-11** Vytvořit `config/local.neon` a ověřit jeho načítání v `Bootstrap.php`
- `[PLÁN]` **F1-12** Nainstalovat `nette/database`
- `[PLÁN]` **F1-13** Nainstalovat PHPStan, `extension-installer`, `phpstan-nette`, strict-rules a deprecation-rules
- `[PLÁN]` **F1-14** Vytvořit `phpstan.neon` s `level: max`
- `[PLÁN]` **F1-15** Opravit chyby kostry na úrovni max (bez baseline)
- `[PLÁN]` **F1-16** Nastavit coding standard (po rozhodnutí F0)
- `[PLÁN]` **F1-17** Nainstalovat testovací nástroj (po rozhodnutí F0)
- `[PLÁN]` **F1-18** Nainstalovat nástroj pro hlídání hranic modulů (po rozhodnutí F0)
- `[PLÁN]` **F1-19** Nainstalovat konzolový nástroj (po rozhodnutí F0)
- `[PLÁN]` **F1-20** Skripty v `composer.json`: `phpstan`, `test`, `cs`, `arch`, `audit`
- `[PLÁN]` **F1-21** `npm init`, instalace `sass`, `esbuild`, `bootstrap`, `@popperjs/core`, `naja`
- `[PLÁN]` **F1-22** Vytvořit `assets/scss/app.scss` a `assets/js/app.js`
- `[PLÁN]` **F1-23** Skripty `build`, `build:css`, `build:js`, `watch:css`, `watch:js`
- `[PLÁN]` **F1-24** `.nvmrc` a `engines` v `package.json`
- `[PLÁN]` **F1-25** Ověřit `npm run build` a načtení souborů v layoutu
- `[PLÁN]` **F1-26** GitHub Actions: `composer validate`, `composer audit`, PHPStan, coding standard, testy
- `[PLÁN]` **F1-27** GitHub Actions: `npm ci`, build, `npm audit`
- `[PLÁN]` **F1-28** Pravidelná kontrola aktualizací závislostí
- `[PLÁN]` **F1-29** README projektu: jak rozjet projekt na pár příkazů
- `[PLÁN]` **F1-30** Ověřit čisté naklonování a rozjetí podle kontrolního seznamu z Lekce 1
- `[PLÁN]` **F1-31** Vytvořit adresářovou strukturu podle STRUKTURA
- `[PLÁN]` **F1-32** Spike: mapování presenterů pro `UI/Front` a `UI/Admin` (ověřit v dokumentaci Nette Application)
- `[PLÁN]` **F1-33** Spike: automatická registrace DI extension jednotlivých modulů ze seznamu modulů
- `[PLÁN]` **F1-34** Spike: načítání výchozích překladů modulů ze souborů
- `[PLÁN]` **F1-35** Docker nebo DDEV konfigurace (po rozhodnutí F0)

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

_Schéma vlny 1 spuštěné, ověřené a napojené na Nette Database Explorer._ Hotovo 0 z 22 kroků.

- `[ROZDĚLÁNO]` **F3-01** Schéma vlny 1 (`001`), základní data (`002`) a demo data (`003`) jsou napsané a čekají na první spuštění v MySQL
- `[PLÁN]` **F3-02** Nainstalovat Nextras Migrations a nastavit adresáře `structures`, `basic-data`, `dummy-data`
- `[PLÁN]` **F3-03** Spustit `001` na čistém MySQL 8.0.16 nebo novějším a zapsat chyby
- `[PLÁN]` **F3-04** Opravit chyby `001` (zejména `CHECK` u sloupců s cizím klíčem, generované sloupce)
- `[PLÁN]` **F3-05** Rozhodnout, zda `001` rozdělit na migrace po modulech
- `[PLÁN]` **F3-06** Spustit `002` a `003`, ověřit data
- `[PLÁN]` **F3-07** Ověřit konvence Exploreru (`ref()`, `related()`) na reálné databázi
- `[PLÁN]` **F3-08** Test: každá tabulka má `id` a cizí klíče odpovídají konvenci
- `[PLÁN]` **F3-09** Test: výjimky (`category.parent_id`, `product_relation.related_product_id`)
- `[PLÁN]` **F3-10** Základ repozitáře nad Explorer: mapování `ActiveRow` na DTO
- `[PLÁN]` **F3-11** Typované `readonly` DTO pro entity vlny 1
- `[PLÁN]` **F3-12** Implementace `Transaction` nad `Nette\Database\Connection`
- `[PLÁN]` **F3-13** Tracy panel pro SQL dotazy ve vývoji
- `[PLÁN]` **F3-14** Kontrola, že žádný dotaz neskládá SQL z řetězců
- `[PLÁN]` **F3-15** Testovací databáze a bootstrap integračních testů
- `[PLÁN]` **F3-16** Příkaz `db:reset` (smazat, migrovat, naplnit) pro vývoj
- `[PLÁN]` **F3-17** Příkaz `db:migrate` pro nasazení
- `[PLÁN]` **F3-18** CI: služba MySQL 8, spuštění migrací a integračních testů
- `[PLÁN]` **F3-19** Implementace hashe kombinace varianty a test shody s SQL `SHA1`
- `[PLÁN]` **F3-20** Diagram vztahů tabulek rozdělený po modulech
- `[PLÁN]` **F3-21** Oddělit databázového uživatele pro migrace a pro aplikaci
- `[PLÁN]` **F3-22** Zásady zálohování a obnovy databáze

### F4: Překlady v databázi

_Modul Translation: systémové texty, jednotky, obsah entit, přepsání administrátorem._ Hotovo 0 z 25 kroků.

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
- `[PLÁN]` **F4-18** Admin: úprava textu (přepsání) a zneplatnění cache
- `[PLÁN]` **F4-19** Admin: přehled zastaralých textů, volba „převzít nový výchozí“ nebo „ponechat můj“
- `[PLÁN]` **F4-20** Admin: vytvoření vlastního textu (`source = custom`)
- `[PLÁN]` **F4-21** Admin: přidání a aktivace nového jazyka
- `[PLÁN]` **F4-22** Oprávnění k úpravě překladů
- `[PLÁN]` **F4-23** Bezpečnost: texty jsou prostý text, test escapování, zákaz HTML
- `[PLÁN]` **F4-24** Test množných čísel v češtině a angličtině
- `[PLÁN]` **F4-25** Test chování bez překladu (záloha, logování)

### F5: Catalog (vlna 1)

_Produkty, varianty, atributy, volby zákazníka, kategorie, obrázky._ Hotovo 0 z 34 kroků.

- `[PLÁN]` **F5-01** Modul `Catalog`: struktura složek a DI extension
- `[PLÁN]` **F5-02** `Api`: `CatalogFacade` a DTO (`ProductDto`, `VariantDto`, `CategoryDto`)
- `[PLÁN]` **F5-03** Doména: `Product`, `ProductVariant`, `Sku`, `ProductStatus`
- `[PLÁN]` **F5-04** Doména: `Attribute`, `AttributeValue`, typy atributů
- `[PLÁN]` **F5-05** Doména: `ProductOption`, `OptionValue`
- `[PLÁN]` **F5-06** Doména: `Category` (strom, zákaz cyklu)
- `[PLÁN]` **F5-07** Pravidlo: produkt má vždy alespoň jednu variantu
- `[PLÁN]` **F5-08** Pravidlo: kombinace varianty je unikátní (hash) a test
- `[PLÁN]` **F5-09** Repozitář produktů (čtení) vracející DTO
- `[PLÁN]` **F5-10** Repozitář produktů (zápis)
- `[PLÁN]` **F5-11** Repozitář kategorií
- `[PLÁN]` **F5-12** Repozitář atributů a hodnot
- `[PLÁN]` **F5-13** Dotaz: detail produktu (varianty, atributy, volby, ceny přes Pricing API)
- `[PLÁN]` **F5-14** Dotaz: výpis produktů v kategorii se stránkováním
- `[PLÁN]` **F5-15** Dotaz: filtr podle atributů (výčet, číslo, rozsah)
- `[PLÁN]` **F5-16** Dotaz: dostupné kombinace os (výběr barvy omezí velikosti)
- `[PLÁN]` **F5-17** Související produkty (`product_relation`)
- `[PLÁN]` **F5-18** Příkaz: vytvoření a úprava produktu
- `[PLÁN]` **F5-19** Příkaz: správa variant (generování z os, ruční přidání)
- `[PLÁN]` **F5-20** Příkaz: správa atributů a hodnot
- `[PLÁN]` **F5-21** Příkaz: správa kategorií
- `[PLÁN]` **F5-22** Příkaz: správa voleb zákazníka
- `[PLÁN]` **F5-23** Události `ProductPublished` a `ProductArchived`
- `[PLÁN]` **F5-24** Obrázky: nahrání, kontrola MIME na serveru, náhodný název, miniatury (rozhodnout nástroj)
- `[PLÁN]` **F5-25** Slugy: generování, unikátnost po jazycích, přesměrování starých adres
- `[PLÁN]` **F5-26** Čištění HTML popisu při uložení (HTMLPurifier)
- `[PLÁN]` **F5-27** Validace vstupů (Nette Forms a doménová validace)
- `[PLÁN]` **F5-28** Jednotkové testy domény
- `[PLÁN]` **F5-29** Integrační testy repozitářů nad demo daty
- `[PLÁN]` **F5-30** Architektonické testy: ostatní moduly používají jen `Api`
- `[PLÁN]` **F5-31** PHPStan level max bez výjimek pro modul
- `[PLÁN]` **F5-32** Texty modulu (`catalog`) v češtině a angličtině
- `[PLÁN]` **F5-33** Demo produkty 1 až 3 ověřené testy
- `[PLÁN]` **F5-34** Dokumentace modulu: co vlastní a jaké API nabízí

### F6: Tax, Pricing, Inventory

_Daně, ceny po měnách, sklad, rezervace._ Hotovo 0 z 25 kroků.

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
- `[PLÁN]` **F6-20** Souběh: dvě objednávky na poslední kus (zámek nebo podmíněný update) a test
- `[PLÁN]` **F6-21** Události `StockLow` a `OutOfStock`
- `[PLÁN]` **F6-22** Admin: příjem zboží a korekce skladu
- `[PLÁN]` **F6-23** Testy rezervace a odpisu
- `[PLÁN]` **F6-24** Texty modulů (cs, en)
- `[PLÁN]` **F6-25** Dokumentace modulů Tax, Pricing, Inventory

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
- `[PLÁN]` **F8-14** Dvoufaktorové ověření administrátora (rozhodnout rozsah)
- `[PLÁN]` **F8-15** Audit přihlášení a změn v administraci
- `[PLÁN]` **F8-16** Politika session (regenerace ID, časové limity, SameSite, Secure, HttpOnly)
- `[PLÁN]` **F8-17** Admin: správa uživatelů a rolí
- `[PLÁN]` **F8-18** Texty (cs, en)
- `[PLÁN]` **F8-19** Testy autentizace a oprávnění
- `[PLÁN]` **F8-20** Ruční bezpečnostní kontrola přihlášení
- `[PLÁN]` **F8-21** Dokumentace

### F9: Orders a Checkout

_Objednávka jako snímek, stavový automat, průchod pokladnou._ Hotovo 0 z 26 kroků.

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

### F10: Shipping, Payments, Mailing

_Rozhraní pro dopravu a platby, e-maily s šablonami v databázi._ Hotovo 0 z 24 kroků.

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
- `[PLÁN]` **F10-19** Šablony e-mailů v databázi po jazycích
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
- `[PLÁN]` **F11-04** Domovská stránka
- `[PLÁN]` **F11-05** Výpis kategorie s filtry (Naja)
- `[PLÁN]` **F11-06** Detail produktu: výběr variant, volby, cena, dostupnost
- `[PLÁN]` **F11-07** Košík a miniaturní košík
- `[PLÁN]` **F11-08** Pokladna po krocích
- `[PLÁN]` **F11-09** Zákaznický účet: přihlášení, registrace, objednávky, adresy
- `[PLÁN]` **F11-10** Chybové stránky (404, 500)
- `[PLÁN]` **F11-11** Základní audit přístupnosti
- `[PLÁN]` **F11-12** SEO základ: title, meta, canonical, hreflang pro cs a en
- `[PLÁN]` **F11-13** Sitemap a robots
- `[PLÁN]` **F11-14** Formuláře: jednotný vzhled a chybové hlášky přes překlady
- `[PLÁN]` **F11-15** Komponenty: karta produktu, cena, stav skladu, vzorník barev
- `[PLÁN]` **F11-16** Formátování cen a jednotek přes `intl`
- `[PLÁN]` **F11-17** Obrázky: responzivní a líné načítání
- `[PLÁN]` **F11-18** Výkon: cache šablon, komprese, hlavičky cache pro `assets`
- `[PLÁN]` **F11-19** Cache busting souborů z buildu
- `[PLÁN]` **F11-20** HTTP testy základních scénářů
- `[PLÁN]` **F11-21** End-to-end test základního nákupu (rozhodnout nástroj)
- `[PLÁN]` **F11-22** Dokumentace

### F12: Admin UI

_Správa obchodu: `UI/Admin`._ Hotovo 0 z 26 kroků.

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
- `[PLÁN]` **F14-04** Audit všech SQL dotazů (SQL injection)
- `[PLÁN]` **F14-05** Audit výstupů a administrátorských textů (XSS)
- `[PLÁN]` **F14-06** Kontrola CSRF napříč formuláři a signály
- `[PLÁN]` **F14-07** Omezení rychlosti: přihlášení, registrace, obnova hesla, pokladna
- `[PLÁN]` **F14-08** Audit nahrávání souborů
- `[PLÁN]` **F14-09** Zabezpečení session a cookies
- `[PLÁN]` **F14-10** Správa tajemství mimo Git a jejich rotace
- `[PLÁN]` **F14-11** Logování bez osobních údajů
- `[PLÁN]` **F14-12** Retence a mazání dat (GDPR)
- `[PLÁN]` **F14-13** Záznamy o zpracování a zásady ochrany osobních údajů
- `[PLÁN]` **F14-14** Cookie lišta a kategorie cookies
- `[PLÁN]` **F14-15** `composer audit` a `npm audit` v CI
- `[PLÁN]` **F14-16** Kontrola licencí závislostí
- `[PLÁN]` **F14-17** Nástroj pro analýzu kódu z hlediska bezpečnosti (rozhodnout)
- `[PLÁN]` **F14-18** Měření pokrytí testy a cíl
- `[PLÁN]` **F14-19** Architektonické testy v CI
- `[PLÁN]` **F14-20** Mutační testy klíčové domény (volitelně)
- `[PLÁN]` **F14-21** Zátěžový test základních stránek
- `[PLÁN]` **F14-22** Externí bezpečnostní audit před spuštěním
- `[PLÁN]` **F14-23** Plán reakce na incident
- `[PLÁN]` **F14-24** Postup nahlášení zranitelnosti
- `[PLÁN]` **F14-25** Dokumentace bezpečnosti

### F15: Provoz a hostování

_Provoz mnoha izolovaných instancí na hostingu autora._ Hotovo 0 z 18 kroků.

- `[PLÁN]` **F15-01** Cílové prostředí (hosting, systém, verze PHP a MySQL)
- `[PLÁN]` **F15-02** Nasazení jedné instance skriptem
- `[PLÁN]` **F15-03** Hromadné nasazení a migrace všech instancí
- `[PLÁN]` **F15-04** Vytvoření nové instance: databáze, uživatel, konfigurace, doména
- `[PLÁN]` **F15-05** Oddělení instancí (uživatelé systému, cesty, limity)
- `[PLÁN]` **F15-06** Zálohování databází a souborů a test obnovy
- `[PLÁN]` **F15-07** Monitoring a upozornění
- `[PLÁN]` **F15-08** Centralizované logy chyb
- `[PLÁN]` **F15-09** Verzování (SemVer) a changelog
- `[PLÁN]` **F15-10** Postup aktualizace instancí a upozornění pro studenty na novou verzi
- `[PLÁN]` **F15-11** Nasazení bez výpadku (rozhodnout)
- `[PLÁN]` **F15-12** Certifikáty SSL a domény obchodů
- `[PLÁN]` **F15-13** Doménové e-maily: SPF, DKIM, DMARC
- `[PLÁN]` **F15-14** Limity zdrojů na instanci
- `[PLÁN]` **F15-15** Podmínky služby a smlouva o zpracování (z F0)
- `[PLÁN]` **F15-16** Zrušení obchodu a export dat
- `[PLÁN]` **F15-17** Provozní dokumentace
- `[PLÁN]` **F15-18** Runbook pro běžné incidenty

### F16: Výuka

_Lekce, cvičení a balíček pro studenty. Lekce navazují na hotové fáze._ Hotovo 0 z 35 kroků.

- `[K ÚPRAVĚ]` **F16-01** Lekce 1: Příprava prostředí a instalace (napsána, čeká na přejmenování a ověření)
- `[PLÁN]` **F16-02** Ověřit Lekci 1 na čistém stroji bez předchozí instalace
- `[PLÁN]` **F16-03** Šablona lekce: cíl, postup, proč tak, časté chyby, cvičení, kontrola
- `[PLÁN]` **F16-04** Sylabus: pořadí lekcí a závislosti
- `[PLÁN]` **F16-05** Lekce 2: Jak Nette zpracuje požadavek (router, presenter, šablona)
- `[PLÁN]` **F16-06** Lekce 3: Struktura aplikace, první modul a striktní DI
- `[PLÁN]` **F16-07** Lekce 4: Hranice modulů a jejich automatická kontrola
- `[PLÁN]` **F16-08** Lekce 5: Databáze, migrace a schéma
- `[PLÁN]` **F16-09** Lekce 6: Nette Database Explorer a mapování na DTO
- `[PLÁN]` **F16-10** Lekce 7: Překlady v databázi
- `[PLÁN]` **F16-11** Lekce 8: Jednotky, množná čísla a ICU MessageFormat
- `[PLÁN]` **F16-12** Lekce 9: Model produktu a variant
- `[PLÁN]` **F16-13** Lekce 10: Peníze a DPH bez chyb
- `[PLÁN]` **F16-14** Lekce 11: Sklad a souběžné objednávky
- `[PLÁN]` **F16-15** Lekce 12: Košík jako doménový objekt
- `[PLÁN]` **F16-16** Lekce 13: Autentizace a autorizace
- `[PLÁN]` **F16-17** Lekce 14: Stavový automat objednávky
- `[PLÁN]` **F16-18** Lekce 15: Pokladna a objednávka jako snímek
- `[PLÁN]` **F16-19** Lekce 16: Rozhraní pro dopravu a platby
- `[PLÁN]` **F16-20** Lekce 17: E-maily a fronta
- `[PLÁN]` **F16-21** Lekce 18: Latte a escapování, proč nepoužívat `noescape`
- `[PLÁN]` **F16-22** Lekce 19: Naja a AJAX v Nette
- `[PLÁN]` **F16-23** Lekce 20: Content Security Policy v praxi
- `[PLÁN]` **F16-24** Lekce 21: Administrace a Datagrid bez doménové logiky
- `[PLÁN]` **F16-25** Lekce 22: Testování: jednotkové, integrační, architektonické
- `[PLÁN]` **F16-26** Lekce 23: PHPStan level max v praxi
- `[PLÁN]` **F16-27** Lekce 24: CI: jak hlídá kvalitu
- `[PLÁN]` **F16-28** Lekce 25: Vyhledávání za rozhraním
- `[PLÁN]` **F16-29** Lekce 26: Nasazení a provoz
- `[PLÁN]` **F16-30** Lekce 27: Jak psát ADR
- `[PLÁN]` **F16-31** Lekce 28: Studentská licence: co smíš a nesmíš
- `[PLÁN]` **F16-32** Cvičení a řešení ke každé lekci
- `[PLÁN]` **F16-33** Automatická kontrola, že příklady v lekcích odpovídají kódu
- `[PLÁN]` **F16-34** Balíček 1.0.0 stable pro studenty
- `[PLÁN]` **F16-35** Postup aktualizace lekcí při změně verzí

### F17: Milník 1: první nasazení

_Uvolnění první verze pro reálný obchod a pro studenty._ Hotovo 0 z 11 kroků.

- `[PLÁN]` **F17-01** Definice milníku 1 (z rozhodnutí F0)
- `[PLÁN]` **F17-02** Ruční průchod celého nákupu v CZK a češtině a v EUR a angličtině
- `[PLÁN]` **F17-03** Pilotní obchod s reálným zákazníkem
- `[PLÁN]` **F17-04** Právní texty: podmínky, zásady ochrany osobních údajů, cookies
- `[PLÁN]` **F17-05** Bezpečnostní audit uzavřen
- `[PLÁN]` **F17-06** Zálohy a obnova ověřeny
- `[PLÁN]` **F17-07** Návod pro obchodníka
- `[PLÁN]` **F17-08** Verze 0.x, release candidate, 1.0.0 stable
- `[PLÁN]` **F17-09** Balíček pro studenty a studentská licence
- `[PLÁN]` **F17-10** Oznámení a kanál podpory
- `[PLÁN]` **F17-11** Retrospektiva a úprava plánu

### F18: Vlna 2: ceny a sklad

_Demo produkty 8, 9, 10, 11: sada, množstevní slevy, předobjednávka, neceločíselné množství._ Hotovo 0 z 18 kroků.

- `[PLÁN]` **F18-01** Sada (8): migrace `bundle` a `bundle_item`, `discount_allocation` (`at_purchase`, `at_return`)
- `[PLÁN]` **F18-02** Sada: doména a dostupnost jako minimum z komponent
- `[PLÁN]` **F18-03** Sada: odpis skladu komponent
- `[PLÁN]` **F18-04** Sada: rozpočet ceny a DPH na složky a snímek řádků
- `[PLÁN]` **F18-05** Sada: částečná vratka jedné složky
- `[PLÁN]` **F18-06** Sada: administrace a testy
- `[PLÁN]` **F18-07** Sada: demo data a lekce
- `[PLÁN]` **F18-08** Množstevní slevy (9): migrace `price_tier`, skupiny zákazníků B2C a B2B, platnost od do
- `[PLÁN]` **F18-09** Množstevní slevy: výpočet v Pricing, zobrazení v detailu a košíku
- `[PLÁN]` **F18-10** Množstevní slevy: administrace, demo data, testy
- `[PLÁN]` **F18-11** Předobjednávka (10): stav dostupnosti a očekávané datum
- `[PLÁN]` **F18-12** Předobjednávka: `stock_notification_subscription`, souhlas GDPR, odeslání a mazání
- `[PLÁN]` **F18-13** Předobjednávka: administrace, demo data, testy
- `[PLÁN]` **F18-14** Neceločíselné množství (11): krok, minimum, `DECIMAL`
- `[PLÁN]` **F18-15** Neceločíselné množství: formulář, sklad v jednotce role, řezání
- `[PLÁN]` **F18-16** Neceločíselné množství: demo data, testy
- `[PLÁN]` **F18-17** Testy nových cen v obou měnách a obou jazycích
- `[PLÁN]` **F18-18** Aktualizovat ZAMER a roadmap po dokončení vlny

### F19: Vlna 3: speciální chování

_Demo produkty 4, 6, 7, 14, 15: dlaždice, licence, věkové omezení, záloha, poukaz._ Hotovo 0 z 22 kroků.

- `[PLÁN]` **F19-01** Dlaždice (4): jednotka prodeje a skladu, převod balení na m²
- `[PLÁN]` **F19-02** Dlaždice: minimální množství a zaokrouhlení nahoru na celé balení
- `[PLÁN]` **F19-03** Dlaždice: šarže a rezervace podle šarže, jedna šarže na objednávku
- `[PLÁN]` **F19-04** Dlaždice: doprava podle váhy a palety
- `[PLÁN]` **F19-05** Dlaždice: demo data, testy, lekce
- `[PLÁN]` **F19-06** Antivirus (6): pool licenčních klíčů (šifrovaně), `license`, `download_grant`
- `[PLÁN]` **F19-07** Antivirus: doručení e-mailem a platnost licence
- `[PLÁN]` **F19-08** Antivirus: DPH podle země zákazníka (OSS) a důkazy o zemi
- `[PLÁN]` **F19-09** Antivirus: demo data, testy, lekce
- `[PLÁN]` **F19-10** Věkové omezení (7): `compliance_rule`, vazba na produkt a kategorii
- `[PLÁN]` **F19-11** Věkové omezení: ověření věku v pokladně a záznam bez zbytečných osobních údajů
- `[PLÁN]` **F19-12** Věkové omezení: omezení dopravy a plateb
- `[PLÁN]` **F19-13** Věkové omezení: právní konzultace (alkohol, tabák)
- `[PLÁN]` **F19-14** Věkové omezení: demo data, testy
- `[PLÁN]` **F19-15** Záloha na obal (14): samostatná cenová složka a daňové zacházení
- `[PLÁN]` **F19-16** Záloha: zobrazení na dokladu odděleně od základu DPH
- `[PLÁN]` **F19-17** Záloha: demo data, testy
- `[PLÁN]` **F19-18** Poukaz (15): `voucher` a `voucher_transaction`
- `[PLÁN]` **F19-19** Poukaz: uplatnění a částečné čerpání
- `[PLÁN]` **F19-20** Poukaz: účetní a daňová specifika (konzultace)
- `[PLÁN]` **F19-21** Poukaz: demo data, testy
- `[PLÁN]` **F19-22** Aktualizovat ZAMER a roadmap po dokončení vlny

### F20: Vlna 4: nejnáročnější

_Demo produkty 5, 12, 13: konfigurátor, produkt s nahráním souboru, předplatné._ Hotovo 0 z 14 kroků.

- `[PLÁN]` **F20-01** Konfigurátor (5): `configurator_group`, `configurator_option`, `configurator_rule`
- `[PLÁN]` **F20-02** Konfigurátor: pravidla kompatibility a cena jako základ plus příplatky
- `[PLÁN]` **F20-03** Konfigurátor: `stock_mode` (sklad komponent nebo hotových konfigurací)
- `[PLÁN]` **F20-04** Konfigurátor: snímek konfigurace v objednávce
- `[PLÁN]` **F20-05** Konfigurátor: administrace, demo data, testy, lekce
- `[PLÁN]` **F20-06** Nahrání souboru (12): `customer_upload` mimo veřejný webroot, kontrola MIME, náhodný název
- `[PLÁN]` **F20-07** Nahrání souboru: náhled a schválení (`proof_approval`), stav objednávky
- `[PLÁN]` **F20-08** Nahrání souboru: bezpečnostní audit
- `[PLÁN]` **F20-09** Nahrání souboru: demo data, testy
- `[PLÁN]` **F20-10** Předplatné (13): `subscription` a `subscription_cycle`
- `[PLÁN]` **F20-11** Předplatné: tokeny platebních karet od brány, opakované platby
- `[PLÁN]` **F20-12** Předplatné: připomínky, zrušení, selhání platby
- `[PLÁN]` **F20-13** Předplatné: demo data, testy
- `[PLÁN]` **F20-14** Aktualizovat ZAMER a roadmap po dokončení vlny

### F21: Funkce, na kterých obchodník stojí

_Seznam „nudných“ funkcí a první várka funkcí navíc oproti PrestaShopu._ Hotovo 0 z 21 kroků.

- `[PLÁN]` **F21-01** Import produktů (CSV a další formáty)
- `[PLÁN]` **F21-02** Export produktů
- `[PLÁN]` **F21-03** Vratky
- `[PLÁN]` **F21-04** Reklamace
- `[PLÁN]` **F21-05** Napojení na srovnávače (Heureka, Zboží.cz)
- `[PLÁN]` **F21-06** SEO nástroje (přesměrování, titulky, strukturovaná data)
- `[PLÁN]` **F21-07** Slevové kódy
- `[PLÁN]` **F21-08** Doporučené produkty
- `[PLÁN]` **F21-09** Doprava: Zásilkovna a další dopravci
- `[PLÁN]` **F21-10** Platby: GoPay, Comgate a další brány
- `[PLÁN]` **F21-11** Export do účetnictví: Pohoda, ISDOC
- `[PLÁN]` **F21-12** Faktury a dobropisy
- `[PLÁN]` **F21-13** Role a oprávnění v administraci (rozšíření)
- `[PLÁN]` **F21-14** OSS: DPH podle země dodání (rozšíření)
- `[PLÁN]` **F21-15** Nástroj na návrh cen v EUR podle kurzu
- `[PLÁN]` **F21-16** Pobídky a akce (modul Promotions s překlady)
- `[PLÁN]` **F21-17** Blog (modul s překlady)
- `[PLÁN]` **F21-18** CMS stránky (modul s překlady)
- `[PLÁN]` **F21-19** Newsletter a souhlasy
- `[PLÁN]` **F21-20** Statistiky a přehledy prodejů
- `[PLÁN]` **F21-21** Zbylé funkce z vlastního seznamu (zhruba 123), zařazované podle priority

## 5. Historie změn dokumentu

| Datum | Změna |
|---|---|
| 4. 11. 2026 | První verze plánu: 511 kroků v 22 fázích, stavy podle dosavadní práce. |
