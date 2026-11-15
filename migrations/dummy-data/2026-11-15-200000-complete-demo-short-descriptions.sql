SET NAMES utf8mb4;

-- Krátké popisy demo produktů musí být smysluplné i samostatně
-- a splnit minimální délku ověřovanou integračním testem.
UPDATE product_translation AS pt
INNER JOIN product AS p ON p.id = pt.product_id
SET pt.short_description = CASE
    WHEN p.code = 'usb-c-cable' AND pt.locale = 'cs'
        THEN 'Odolný USB-C kabel v délkách 1 m a 2 m pro každodenní použití'
    WHEN p.code = 'gan-charger' AND pt.locale = 'en'
        THEN 'Compact GaN wall charger for everyday device charging'
    ELSE pt.short_description
END
WHERE (p.code = 'usb-c-cable' AND pt.locale = 'cs')
   OR (p.code = 'gan-charger' AND pt.locale = 'en');
