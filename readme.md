# ArgoMi-BestShop

ArgoMi-BestShop je modulární e-shop postavený na PHP 8.3+ a Nette 3.2. Projekt je vyvíjen jako modulární monolit s oddělenými oblastmi Front a Admin, databázovým přístupem přes Nette Database Explorer a SQL migracemi spravovanými Nextras Migrations.

## Dokumentace

Začni v [docs/README.md](docs/README.md). Kanonické dokumenty:

- [Záměr a rozhodnutí](docs/ZAMER.md)
- [Struktura aplikace](docs/STRUKTURA.md)
- [Databáze a migrace](docs/DATABASE.md)
- [Překladový subsystém](docs/TRANSLATIONS.md)
- [Roadmap](docs/ROADMAP.md)
- [Architektonická rozhodnutí](docs/adr/)

## Technologie

- PHP 8.3+
- Nette (Composer constraints `^3.2`, lockfile resolves 3.3.x) a Latte 3
- MySQL 8.x / InnoDB / utf8mb4
- Nette Database Explorer
- Nextras Migrations (SQL)
- PHPStan level max
- Nette Tester
- Sass + esbuild, Bootstrap 5.3.8 + Bootswatch Cosmo 5.3.8
- Naja 3.3.3
- AdminLTE 4.10.2 + Bootstrap Icons 1.13.2 (Admin)
- HugeRTE 1.0.15 (lokální WYSIWYG)
- Ublaboo DataGrid 7.2.0
- Intervention Image 4.4.0 + GIF driver
- HTMLPurifier 4.19.1

Vite se nepoužívá. Assety se sestavují vlastními skripty Sass a esbuild.

## Aktuální implementační rozsah

- Nejucelenější implementovanou oblastí je Catalog v administraci: kategorie, produkty, atributy, varianty, zákaznické volby a mediální vazby.
- Media poskytuje soukromou knihovnu, bezpečné uploady a explicitní publikování veřejných derivátů.
- Identity poskytuje základní administrátorské přihlášení; nejde o kompletní RBAC/ACL.
- Veřejný produktový frontend, košík a checkout nejsou dokončené. Přítomnost jejich adresářů nebo databázových tabulek neznamená hotové uživatelské workflow.

## Vývojové prostředí

Projekt je vyvíjen v prostředí PHP/Composer, Node.js/npm a MySQL. Konkrétní lokální nastavení databáze a přístupových údajů patří do lokální konfigurace, která se necommitne.

Základní příkazy:

```bash
composer install
npm install
npm run build
composer run phpstan
composer run tester
composer db:reset # destruktivní: pouze lokální vývojová databáze argomi_bestshop
```

Dostupné příkazy a jejich skutečný stav ověřuj v `composer.json` a `package.json`. Předpokládej, že migrace spouští Nextras Migrations podle `config/common.neon`; neprováděj reset produkční databáze.

## Databázové migrace

Migrace jsou v `migrations/` a mají tři skupiny:
- `structures/`
- `basic-data/`
- `dummy-data/`

Demo data zůstávají i v produkční instalaci jako demonstrační obsah. Implementace musí zabránit tomu, aby demo objednávky spustily skutečné platby, expedici nebo zákaznické e-maily.

## Licence a repozitář

GitHub repozitář je veřejný, ale projekt není open source. Zdrojový kód je zveřejněn bez udělení licence k užití; všechna práva jsou vyhrazena. Konkrétní právní podmínky distribuce a případná studentská licence se doplní samostatně. Veřejná dostupnost repozitáře není svolením ke kopírování, úpravám ani šíření.

## Bezpečnost

- Nikdy necommituj `config/local.neon`, přístupové údaje, tokeny ani produkční data.
- `app/`, `config/`, `log/` a `temp/` nesmí být webroot.
- Tracy debugger musí být v produkci vypnutý.
- Nepoužívej Latte `noescape`.
- Databázové dotazy patří do repozitářů a musí být parametrizované.
- Neprováděj produkční migraci bez ověřené zálohy.
