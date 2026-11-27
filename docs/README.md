# Dokumentace ArgoMi-BestShop

Toto je vstupní bod pro aktuální dokumentaci projektu. Dokumentace popisuje **schválený záměr, skutečnou strukturu repozitáře, aktuální databázový základ a plán další práce**.

## Kanonické dokumenty

| Dokument | Účel |
|---|---|
| [Audit 27. 11. 2026](AUDIT-2026-11-27.md) | Ověřený stav migrací, schématu, roadmapy, modulů, technologií a otevřených nesouladů |
| [ZÁMĚR](ZAMER.md) | Produktová vize, zásady, technologie a schválená rozhodnutí |
| [STRUKTURA](STRUKTURA.md) | Skutečné členění aplikace, odpovědnosti modulů a pravidla závislostí |
| [DATABASE](DATABASE.md) | Aktuální databázové schéma, migrace, konvence a seed data |
| [TRANSLATIONS](TRANSLATIONS.md) | Cílový překladový model, ochrana systémových textů a přechod ze současného schématu |
| [Translation module README](../app/Modules/Translation/README.md) | Praktické použití veřejného API, prezentery, formátování, správa jazyků a diagnostika |
| [Catalog module README](../app/Modules/Catalog/README.md) | Katalogové API, produktové obrázky a administrační funkce |
| [Media module README](../app/Modules/Media/README.md) | Bezpečné úložiště, deriváty, knihovna a publikování médií |
| [Identity module README](../app/Modules/Identity/README.md) | Stav administrátorské Identity foundation, seedované role, bezpečnostní hranice a dosud neimplementované části |
| [Tax module README](../app/Modules/Tax/README.md) | Aktuální minimální čtecí kontrakt daňových tříd |
| [Admin UI README](../app/UI/Admin/README.md) | Layout AdminLTE, navigace, DataGrid, produktový editor a Media UI |
| [ROADMAP](ROADMAP.md) | Jediný aktuální seznam úkolů, stavů a milníků |
| [ADR](adr/) | Záznamy důležitých architektonických rozhodnutí |

## Zásada pravdivého stavu

Dokumenty rozlišují:
- **Existuje** – je to přítomné v repozitáři nebo ověřené v databázi.
- **Schváleno** – autor rozhodl, že se tím projekt bude řídit.
- **Plán** – ještě není implementováno.
- **Otevřeno** – čeká na rozhodnutí nebo odborné ověření.

Existence adresáře nebo SQL tabulky sama o sobě neznamená, že je hotová funkční implementace.

## Zdroj pravdy

- Aktuální strom aplikace: skutečné soubory v `app/`.
- Databázové schéma a seed data: SQL soubory v `migrations/`.
- Konfigurace migrací: `config/common.neon`.
- Stav vývoje: [ROADMAP](ROADMAP.md).
- Přesný migrační inventář a počty SQL souborů: [DATABASE](DATABASE.md).
- Rozhodnutí: [ZÁMĚR](ZAMER.md) a záznamy v [ADR](adr/).

Při rozporu mezi starou verzí dokumentu a tímto souborem platí aktuální kanonické dokumenty a novější výslovné rozhodnutí autora. Historické verze jsou v [archive](archive/).

## Práce s dokumentací

Po každé pracovní session:
1. aktualizovat dotčený stav v ROADMAP,
2. zapsat nové trvalé rozhodnutí do ZÁMĚRU nebo ADR,
3. aktualizovat STRUKTURU či DATABASE, pokud se změnila architektura nebo schéma,
4. ověřit, že README a AGENTS.md neobsahují zastaralé instrukce.
