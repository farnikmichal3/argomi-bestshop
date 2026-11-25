# Modul Media

Modul `Media` vlastní obecné mediální soubory nezávisle na jejich obchodním použití.

## Hranice modulu

- Veřejné kontrakty pro ostatní moduly patří do `Api/`.
- Doménová pravidla a invarianty mediálních položek patří do `Domain/`.
- Aplikační případy užití, validace uploadu a interní DTO patří do `Application/`.
- Persistence, obrazové zpracování a fyzické úložiště patří do `Infrastructure/`.
- `ActiveRow` ani fyzické cesty souborů neopouštějí infrastrukturní vrstvu.
- Ostatní moduly používají výhradně veřejné Media API; nesmějí přistupovat k interním repozitářům ani souborovému úložišti.

## Odpovědnost

Media spravuje identitu souboru, neměnný originál, ověřená metadata, znovu vytvořitelné deriváty, bezpečné fyzické uložení a administrační knihovnu. Nezná produkty, varianty, objednávky ani editor obsahu.

Catalog spravuje produktové a variantní vazby na mediální položky. Odstranění vazby nesmí automaticky odstranit soubor; Media odmítne smazání položky, dokud existuje aktivní reference.

## Bezpečnostní pravidla

- Soubory se ukládají mimo veřejný adresář `www/`; veřejné URL pro zápis neexistují.
- Upload je dostupný pouze přes autentizovanou administraci s kontrolou oprávnění na prezentační i aplikační vrstvě.
- Typ souboru se určuje dekódováním obsahu, nikoliv podle přípony nebo klientského MIME typu.
- Platí limity velikosti zdroje a počtu pixelů; animované a nedekódovatelné soubory se odmítají.
- Originál zůstává beze změny. Deriváty se vytvářejí z originálu podle centrálního receptu.
- Všechny cesty úložiště jsou interní neprůhledné klíče; uživatelský vstup nikdy neurčuje cestu souboru.
