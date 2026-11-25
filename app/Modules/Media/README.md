# Modul Media

Modul `Media` vlastní obecné mediální soubory nezávisle na jejich obchodním použití.

## Hranice modulu

- Veřejné kontrakty pro ostatní moduly patří do `Api/`.
- Aplikační případy užití, validace uploadu a interní DTO patří do `Application/`.
- Databázové adaptéry, obrazové zpracování a fyzické úložiště patří do `Infrastructure/`.
- `ActiveRow` ani fyzické cesty souborů neopouštějí infrastrukturní vrstvu.
- Ostatní moduly používají výhradně veřejné Media API; nesmějí přistupovat k interním repozitářům ani souborovému úložišti.

## Odpovědnost

Media spravuje identitu souboru, neměnný originál, ověřená metadata, znovu vytvořitelné deriváty, bezpečné fyzické uložení a knihovnu médií. Nezná produkty, varianty, objednávky ani editor obsahu.

Catalog spravuje produktové a variantní vazby na mediální položky. Odstranění vazby nesmí automaticky odstranit soubor; Media odmítne smazání položky, dokud existuje aktivní reference.

## Aktuální implementační základ

- Upload je vystaven přes `MediaAssetWriter`; čtení knihovny přes `MediaAssetReader`.
- Originály se zachovávají beze změny. Deriváty `thumbnail`, `listing` a `detail` vznikají z originálu ve WebP.
- Soubory jsou uloženy pod neprůhlednými relativními klíči mimo `www/`.
- Zpracování kontroluje skutečný obrazový obsah, limit 20 MB a limit 40 megapixelů.
- Mazání databázového záznamu je chráněno cizími klíči před odstraněním používaného média.

## Bezpečnostní pravidla

- Upload je dostupný pouze přes autentizovanou administraci s kontrolou oprávnění na prezentační i aplikační vrstvě.
- Typ souboru se určuje dekódováním obsahu, nikoliv podle přípony nebo klientského MIME typu.
- Animované a nedekódovatelné soubory se odmítají.
- Všechny cesty úložiště jsou interní neprůhledné klíče; uživatelský vstup nikdy neurčuje cestu souboru.
