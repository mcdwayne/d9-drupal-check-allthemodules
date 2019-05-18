# Commerce Billbee

Provides functionality for synchronising a Drupal Commerce shop with Billbee.

## Setup

### Drupal Commerce

* Enable the commerce_billbee module and dependencies.
* Configure API key at ```/admin/commerce/config/billbee```
* If you have shippable products, configure your order workflow with fulfillment to allow Billbee flagging orders as
  shipped.

### Billbee

* Create new shop (Eigenen webshop (Billbee API) hinzufügen).
* Enter the API key you created in Drupal Commerce as Key.
* Optional: If you want stock synced from Billbee to Drupal Commerce, you need to enable an extra (paid) module in
  Billbee and configure your shop connection in Billbee to use this feature. See 
  https://support.billbee.de/support/solutions/articles/5000733483-automatischer-bestandsabgleich for details.

## Customisation

See commerce_billbee.api.php for altering synchronisation data.

## Maintainer

* Jimmy Henderickx ([@strykaizer](https://github.com/strykaizer)) https://drupal.org/user/744628

## Sponsors

* fratzhozen https://fratzhosen.de/
* Brandle https://brandle.be/