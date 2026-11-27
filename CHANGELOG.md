# Changelog

All notable, merchant-relevant changes to ArgoMi-BestShop are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Development of the first production release is in progress. No production release has been published yet.

### Added

- Administrátorská knihovna médií s bezpečným nahráváním a neveřejnými soubory ve výchozím stavu.
- Výslovné publikování obrázků; veřejná část zpřístupňuje pouze publikované odvozené varianty, nikoliv originály.
- Výběr publikovaných obrázků přímo v editoru produktových popisů HugeRTE.
- Záložková editace produktu rozdělená do pěti pracovních částí.
- Administrátorská správa souvisejících produktů: typované vztahy, výběr aktivních produktů, přidávání, odebírání a řazení.
- Kontrola aktivace produktu: produkt lze aktivovat až po vytvoření alespoň jedné aktivní varianty a nastavení právě jedné výchozí aktivní varianty.
- Nevratná archivace produktů a vytvoření nového konceptu z archivovaného produktu; kopírují se znovupoužitelné katalogové údaje a aktuální ceny, nikoliv EAN, skladové stavy nebo obchodní historie.

### Recording rules

- Keep entries in reverse chronological order.
- Group changes under **Added**, **Changed**, **Deprecated**, **Removed**, **Fixed**, or **Security**.
- Describe the impact in language understandable to a shop operator; include relevant action or migration notes when a change requires them.
- Record only changes relevant to merchants or the operation of their shops. Internal refactoring, tooling changes, and development-only maintenance do not belong in this public-facing changelog.
- For each production release, add a version heading with its release date and move the applicable entries out of **Unreleased**.
- Production release versions use SemVer (`MAJOR.MINOR.PATCH`) and are marked with matching Git tags.
