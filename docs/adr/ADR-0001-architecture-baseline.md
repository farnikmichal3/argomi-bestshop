# ADR-0001: Základní architektura ArgoMi-BestShop

- **Stav:** přijato autorem
- **Datum:** 8. 11. 2026
- **Rozsah:** architektura, prezentace, překlady, distribuce

## Kontext

Původní dokumenty obsahovaly alternativní návrhy adresářů, neaktuální předpoklady o distribuci a překladový model s centrální tabulkou. Aktuální strom aplikace a následná rozhodnutí autora tyto varianty nahrazují.

## Rozhodnutí

1. Projekt zůstává modulárním monolitem, jedna instance a databáze na jeden obchod.
2. Zachovává se skutečné členění `app/Core`, `app/Modules`, `app/Presentation`, `app/Shared` a `app/UI`.
3. UI se dělí na Admin, Front a Shared. `Presentation` je samostatná frameworková infrastruktura.
4. Překlady se dělí podle domén. Obsahové překlady vlastní doménové moduly; Translation vlastní společnou infrastrukturu.
5. Systémové výchozí překlady dodávané aplikací jsou chráněné před přímou úpravou administrátorem. Vlastní překlady jsou oddělené.
6. GitHub repozitář je veřejný, ale kód je zveřejněn bez licence k použití; všechna práva vyhrazena, právní texty se doplní.
7. Demo data zůstávají i v produkci, ale nesmí spouštět skutečné externí operace.

## Důsledky

- STRUKTURA, ZAMĚR, DATABASE a TRANSLATIONS jsou aktualizovány.
- Současná tabulka `translation_message` je přechodná; vyžaduje novou migrační strategii.
- Staré migrační soubory se nepřepisují.
- Nette presenter mapping a několik technických závislostí se musí ověřit v implementačních úkolech.
