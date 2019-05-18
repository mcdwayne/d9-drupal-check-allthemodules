# Cumul.io

Provides functionality for cumul.io integration.

## Installation
Since the module requires external libraries, Composer or Ludwig must be used.

### Composer
If your site is [managed via Composer](https://www.drupal.org/node/2718229), 
use Composer to download the module, which will also download the required
libraries:
   ```sh
   composer require "drupal/cumulio"
   ```

Use ```composer update drupal/cumulio --with-dependencies``` to update to a new
release.

### Ludwig
Otherwise, download and install [Ludwig](https://www.drupal.org/project/ludwig)
which will allow you
to download the libraries separately:
1) Download Cumul.io into your modules folder.
2) Use one of Ludwig's methods to download libraries:

    a) Run the ```ludwig:download``` Drupal Console command or the 
    ```ludwig-download``` Drush command.

    b) Go to ```/admin/reports/packages``` and download each library manually, 
    then place them under cumulio/lib as specified.

3) Enable Cumul.io.

Note that when using Ludwig, updating the module will require re-downloading 
the libraries.

Composer is recommended whenever possible.

## Useful modules

### Token Filter

The module [Token Filter](https://www.drupal.org/project/token_filter) allows
you to use the tokens created in this module in wysywig fields.
