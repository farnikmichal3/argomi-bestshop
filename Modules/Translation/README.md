# Modul Translation

Modul `Translation` poskytuje společnou infrastrukturu pro systémové překlady, správu jazyků, bezpečné vlastní překlady a lokalizované formátování. Vlastnické moduly nadále odpovídají za své překladové klíče a obsahové překlady.

## Základní pravidla

- Databáze je jediným runtime zdrojem překladových hodnot. Modul nepoužívá souborové katalogy ani runtime fallback do PHP, Latte, NEON nebo JSON.
- Každý systémový klíč patří právě jedné vlastnické doméně: `Catalog`, `Cart`, `Checkout` nebo `Admin`.
- Obsahové překlady zůstávají v tabulkách modulu, který vlastní překládanou entitu.
- Výchozí systémové texty se dodávají verzovanými SQL migracemi/seedy. Seed smí doplnit chybějící záznam, nesmí přepsat existující systémový ani vlastní text.
- Systémové záznamy `source=core` jsou chráněné. Administrátor spravuje pouze vlastní záznamy `source=custom`.
- Veškerý vykreslovaný text zůstává pod standardním escapováním Latte; `noescape` se nepoužívá.

Podrobný závazný model, databázové invarianty a historii rozhodnutí popisuje [docs/TRANSLATIONS.md](../../../docs/TRANSLATIONS.md).

## Veřejné API

Ostatní moduly a prezentační vrstvy smějí záviset pouze na třídách v `Api/`. Interní implementace v `Application/`, `Domain/` a `Infrastructure/` nejsou veřejným kontraktem.

| Kontrakt | Odpovědnost |
|---|---|
| `TranslatorFactory` + `TranslationDomain` | Vytvoření standardního Nette překladače pro explicitního vlastníka a locale |
| `MessageTranslationReader` | Čtecí přehled systémových překladů pro administraci |
| `MessageTranslationWriter` | Vytváření, změna a odstranění pouze vlastních systémových textů |
| `LocaleManager` | Přidávání jazyků a změna jejich aktivního stavu |
| `TranslationPermission` | Serverové oprávnění ke čtení a správě překladů |
| `TranslationDiagnostics` | Registrace vývojové diagnostiky |
| `LocaleNumberFormatter` | Formátování čísel a přesných peněžních částek podle explicitního locale |
| `UnitFormatter` | Formátování symbolu a ICU množného tvaru jednotky |

Veřejné DTO, enumy a typované výjimky jsou umístěny v `Api/Dto`, `Api/Event` a přímo v `Api/`. Interní `MessageDomain`, `MessageStore`, databázové adaptéry a `DatabaseTranslatorFactory` se mimo modul nepoužívají.

## Použití systémových překladů v prezentaci

Presenter, jehož Latte šablony používají systémové texty, dědí z `App\Presentation\TranslatedPresenter` a explicitně určí vlastnickou doménu:

```php
protected function getTranslationDomain(): TranslationDomain
{
    return TranslationDomain::Catalog;
}
```

Překladač se do šablony nastaví automaticky. Jazyk se ověřuje proti aktivním jazykům z databáze; není-li parametr `lang` přítomen, použije se výchozí aktivní locale.

V Latte používej nativní překladovou syntaxi:

```latte
<h1>{_'catalog.title'}</h1>
<p>{_'catalog.products_count', count: $count}</p>
```

Klíče jsou stabilní identifikátory, nikoliv texty. Presenter nesmí odvozovat doménu z názvu třídy ani z prefixu klíče. Fallback hledá nejprve požadované locale a potom výchozí aktivní locale, vždy pouze ve stejné vlastnické doméně; pokud hodnota chybí, vrátí stabilní klíč a zaznamená diagnostiku.

## Formátování

Locale se předává explicitně. Pro běžná čísla a peníze používej `LocaleNumberFormatter`; peněžní částky se předávají jako `Money` a zůstávají v celočíselných minor units. Implementace nesmí převádět celou částku na float ani provádět měnovou konverzi.

Pro jednotky používej `UnitFormatter`. Překlad symbolu pochází z vlastnického úložiště jednotek; množný tvar názvu používá ICU a přesné `Quantity`. Množství se předává jako desetinný řetězec, nikoliv jako float. Chyby formátování se propagují typovanými výjimkami.

## Správa jazyků a vlastních textů

- `LocaleManager` spravuje seznam jazyků a jejich aktivitu. Výchozí jazyk nelze deaktivovat.
- `MessageTranslationReader` poskytuje read-only přehled systémových záznamů.
- `MessageTranslationWriter` dovoluje pouze vlastní texty. Zápis odmítá prázdné hodnoty a HTML a po úspěšné změně invaliduje příslušnou cache.
- Serverová autorizace používá role `translation_viewer` (čtení) a `translation_manager` (správa). Kontrola musí probíhat při zobrazení i při zpracování požadavku; skrytí ovládacího prvku není bezpečnostní opatření.
- Formuláře používají CSRF ochranu Nette Forms a výstup standardní escapování Latte.

## Cache a diagnostika

Systémové katalogy jsou cacheovány podle kombinace vlastnické domény a locale. Cache je pouze optimalizace; databáze zůstává zdrojem pravdy. Zapisovací operace invalidují příslušný katalog až po úspěšném zápisu.

Příkaz `translations:check` je read-only kontrola statických klíčů v `app/UI`. Porovnává je s databázovými záznamy ve všech aktivních jazycích a hlásí chybějící jazykové hodnoty i nepoužívané klíče. Neopravuje ani nemění data. Dynamicky sestavené klíče a použití mimo skenovaný strom vyžadují samostatné pokrytí.

Chybějící překlady se zaznamenávají strukturovaně a deduplikují v rámci požadavku. Vývojový Tracy panel zobrazuje diagnostiku pouze v odpovídajícím vývojovém režimu; hodnoty jsou escapované.

## Databázové vlastnictví

| Vlastník | Systémová tabulka |
|---|---|
| Catalog | `catalog_message_translation` |
| Cart | `cart_message_translation` |
| Checkout | `checkout_message_translation` |
| Admin | `admin_message_translation` |

Modul Translation vlastní společnou infrastrukturu a správu jazyků, nikoliv obsahové překlady ostatních modulů. Doménové tabulky se nikdy neslučují do jednoho centrálního úložiště. Tabulku vlastníka vybírá interní pevné mapování domény; uživatelský vstup nesmí určovat název tabulky.

## Struktura a registrace

Modul dodržuje členění `Api/`, `Application/`, `Domain/` a `Infrastructure/`. DI extension je `Infrastructure/DI/TranslationExtension` a registruje se automaticky přes projektový `ModuleRegistry` a konvenci názvů. Ostatní moduly smějí používat pouze veřejné API.

V modulu nevytvářej `Resources/translations` ani jiné souborové katalogy. Nové systémové texty přidávej do vlastnické domény verzovaným idempotentním SQL seedem a pokryj je integračním testem. Historické migrace neupravuj; změny databáze přidávej dopřednou migrací.

## Související dokumentace

- [Překladový standard a databázový model](../../../docs/TRANSLATIONS.md)
- [Struktura aplikace a hranice modulů](../../../docs/STRUKTURA.md)
- [Databázové schéma a migrace](../../../docs/DATABASE.md)
- [Plán vývoje](../../../docs/ROADMAP.md)
