# Modul Tax

Modul `Tax` vlastní daňové třídy a sazby. V rámci F5-16 je zpřístupněn
minimální veřejný read kontrakt `Api/TaxClassOptionReader` pro administrační
výběry daňové třídy produktu.

- Veřejné DTO `Api/Dto/TaxClassOption` obsahuje pouze ID, stabilní kód a název.
- `Infrastructure/Persistence/TaxClassOptionQueryService` čte nabídku přes
  Nette Database Explorer a mapuje řádky na DTO.
- Katalogový editor používá pouze veřejné Tax API; modul Catalog nečte tabulku
  `tax_class` přímo.
- Správa daňových tříd, sazeb, zemí a časové platnosti zůstává samostatným
  rozsahem F6.
