# Admin UI

Administrace používá společný Latte layout \`@layout.latte\` postavený na **AdminLTE 4**. Login zůstává samostatnou stránkou bez administrační navigace.

## AdminLTE 4

- Verze šablony: \`admin-lte@4.10.2\` (MIT).
- Základ: Bootstrap 5.3; jQuery není používáno.
- Ikony: Bootstrap Icons \`1.13.2\`.
- Struktura layoutu: \`app-wrapper\`, \`app-header\`, \`app-sidebar\`, \`app-main\`, \`app-footer\`.
- Responzivní levé menu ovládá AdminLTE PushMenu přes \`data-lte-toggle="sidebar"\`.
- Navigace se vykresluje z jediného sdíleného layoutu a nové odkazy se doplňují postupně s oprávněním a překlady.

## Navigace a překlady

\`AdminAccessTrait\` ověřuje přihlášení při startu presenteru a před vykreslením poskytuje layoutu:

- \`canViewCatalog\` z \`CatalogPermission::canView()\`;
- \`canViewTranslations\` z \`TranslationPermission::canView()\`;
- \`adminLocaleCode\` z výchozího aktivního locale;
- \`adminTranslator\`, překladač domény \`Admin\` nezávislý na překladové doméně aktuální stránky.

Navigační popisky Produkty a Kategorie se proto vždy překládají přes doménu Admin, nikoliv přes doménu Catalog právě otevřeného presenteru. Skrytí odkazu je pouze prezentační pomůcka; cílové presentery nadále samostatně kontrolují oprávnění.

## Editor produktů (F5-16)

- Seznam produktů zůstává stránkovaným DataGrid read modelem.
- Akce vytvoření a úpravy vedou na samostatné stránky presenteru
  `Admin:Products:create` a `Admin:Products:edit`.
- Formulář základních údajů pracuje s aktivními jazyky z databáze,
  hierarchickým výběrem kategorií, stavem, typem a daňovou třídou.
- Zápisy předává aplikačnímu `ProductWriteService`; presenter neobsahuje
  databázové dotazy ani vlastní transakční logiku.
- Oprávnění `CatalogPermission::canManage()` se kontroluje v presenteru
  i znovu v aplikační zapisovací službě.
- Vnořené hodnoty formuláře (jazykové kontejnery a výběr kategorií) se
  předávají handleru jako pole pomocí typovaného `array` návratového mapování
  Nette Forms; výchozí `ArrayHash` se nesmí zaměňovat za PHP pole.
- Po úspěšném zápisu následuje Post/Redirect/Get a potvrzení přes Nette flash
  session, které vykresluje společný administrační layout.

## Matice variant (F5-17)

Z editoru produktu vede odkaz na presenter `Admin:Products:Variants`. Podle Nette presenter mappingu `Admin: [App\\UI\\Admin, *, **Presenter]` je třída umístěna v namespace `App\\UI\\Admin\\Products\\Variants` a souboru `Products/Variants/VariantsPresenter.php`; šablona matice zůstává explicitně načítána z `Products/variants.latte`.
Matice načítá veřejný `ProductVariantMatrixReader` a změny předává
veřejnému `ProductVariantGenerator`; UI neobsahuje databázové
dotazy ani vlastní výpočet hashů. Serverová kontrola
`CatalogPermission::canManage()` platí pro načtení i změnu.

Stránka zobrazuje existující i chybějící kombinace. Obchodník vybírá pouze
chybějící kombinace, které chce vytvořit. Generování je transakční a idempotentní;
existující varianty ani jejich SKU, aktivita, výchozí příznak, ceny, sklad,
obrázky a historické vazby se nemění. Nové varianty dostanou dočasné SKU,
aktivní stav a nejsou automaticky označeny jako výchozí; ruční správa údajů
konkrétní varianty navazuje ve F5-18.

Enum hodnoty se načítají z číselníku, logické osy mají hodnoty Ano/Ne a
číselné/textové hodnoty se v této iteraci odvozují z existujících variant.
Pokud taková osa zatím nemá žádnou hodnotu, stránka generování zablokuje;
zadávání nových hodnot naváže na správu atributů a os. Matice je omezena
na 256 kombinací. F5-17 je dokončeno; lokální testy i funkčnost stránky
v prohlížeči byly potvrzeny uživatelem.
## JavaScript a CSS

- \`assets/js/admin.js\` je jediný vstupní bod Adminu. Načte AdminLTE CSS, Bootstrap Icons, \`shared.js\` a AdminLTE JavaScript v tomto pořadí.
- \`shared.js\` inicializuje Bootstrap, Naja a Nette Forms právě jednou.
- esbuild z JS vstupu vytvoří \`www/assets/admin.js\` a společný CSS bundle \`www/assets/admin.css\`.
- \`assets/scss/admin.scss\` obsahuje pouze vlastní styly administrace a DataGridu; Sass jej sestavuje do \`www/assets/admin-custom.css\`.
- Layout načítá \`admin.css\` a \`admin-custom.css\` právě jednou. Jednotlivé šablony nesmějí znovu vkládat ani inicializovat Bootstrap, Naja, Nette Forms nebo AdminLTE.
- Presenterové skripty patří do Latte bloku \`scripts\`, který se vykreslí až po společném bundle.

## Login

Login používá vlastní jednoduchou šablonu bez administračního layoutu a navigace. Sdílí pouze CSS bundle a formulářové styly.
