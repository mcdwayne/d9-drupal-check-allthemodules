# Odoo
This module is not intended to be used standalone. It offers a proxy to Odoo and can be
used by developers to integrate Drupal with Odoo.
## Installation
Download the module:

`composer require drupal/odoo`

Install the module:

`drush -y en odoo`
## Configuration
Fill out the connection details for the Odoo API you would like to connect to on
/admin/config/services/odoo.
## Usage
Load the Odoo client service:

`$client = \Drupal::getContainer()->get('odoo.client')->client();`

Documentation about using the client: https://github.com/jacobsteringa/OdooClient