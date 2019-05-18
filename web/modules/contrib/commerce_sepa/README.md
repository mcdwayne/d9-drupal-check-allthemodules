### SUMMARY
This module provides a simple SEPA (Single Euro Payments Area) payment method with IBAN validation for Drupal Commerce. After completing the order a mail with the SEPA Direct Debit Mandate can be sent to the customer with all necessary information that the customer must return completed and signed.

### REQUIREMENTS
- [PHP-IBAN library](https://github.com/globalcitizen/php-iban).

### INSTALLATION
Using composer:
```
composer require drupal/commerce_sepa --sort-packages
```

This module adds support to [Ludwig](https://www.drupal.org/project/ludwig), as alternative to composer.

### CONFIGURATION
- Go to admin/commerce/config/payment-gateways
- Add a new SEPA payment gateway
- Edit your gateway settings and configure your "SEPA Direct Debit Mandate"

### SPONSORS
- [Fundación UNICEF Comité Español](https://www.unicef.es)

### CONTACT
Developed and maintained by Cambrico (http://cambrico.net).

Get in touch with us for customizations and consultancy:
http://cambrico.net/contact

#### Current maintainers:
- Pedro Cambra [(pcambra)](https://www.drupal.org/u/pcambra)
- Manuel Egío [(facine)](https://www.drupal.org/u/facine)