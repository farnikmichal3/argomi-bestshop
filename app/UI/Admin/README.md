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

## JavaScript a CSS

- \`assets/js/admin.js\` je jediný vstupní bod Adminu. Načte AdminLTE CSS, Bootstrap Icons, \`shared.js\` a AdminLTE JavaScript v tomto pořadí.
- \`shared.js\` inicializuje Bootstrap, Naja a Nette Forms právě jednou.
- esbuild z JS vstupu vytvoří \`www/assets/admin.js\` a společný CSS bundle \`www/assets/admin.css\`.
- \`assets/scss/admin.scss\` obsahuje pouze vlastní styly administrace a DataGridu; Sass jej sestavuje do \`www/assets/admin-custom.css\`.
- Layout načítá \`admin.css\` a \`admin-custom.css\` právě jednou. Jednotlivé šablony nesmějí znovu vkládat ani inicializovat Bootstrap, Naja, Nette Forms nebo AdminLTE.
- Presenterové skripty patří do Latte bloku \`scripts\`, který se vykreslí až po společném bundle.

## Login

Login používá vlastní jednoduchou šablonu bez administračního layoutu a navigace. Sdílí pouze CSS bundle a formulářové styly.
