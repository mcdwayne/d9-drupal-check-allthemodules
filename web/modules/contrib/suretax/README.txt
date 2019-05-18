INTRODUCTION
============
The Drupal Commerce Connector for Suretax is a Drupal compliant module that
integrates the Drupal Commerce check-out process with Suretax and is used for sales tax calculations.

The module supports two modes - Development(for testing purpose) and Live(RealTime transactions).

REQUIREMENTS
============
a) The service uses the SureTax ReST api for processing transactions.
b) The server PHP configuration must support cURL

NEW INSTALLATION
=================
Installing the module is done as for any custom Drupal Commerce module

a) Unzip & copy the folder "suretax" to the location shown below,
or in accordance with your Drupal Commerce configuration.

YOURSITE directory modules/suretax or modules/custom/suretax

b) Enable the module (SureTax) in the usual way.
c) After successful installation a commerce line item type and product variation will be created.

CONFIGURATION
=============
Select Store -> Configuration -> Suretax (YOURSITE/admin/commerce/config/suretax)

Complete the information requested, as is applicable to edition selected.
Save the form - Suretax settings - on completion.

GENERAL
=======
-> Select Mode(Development/Live).
-> Enter all details like clientId, ValidationKey, Suretax Post and Cancel API's

WORKING
=======
-> When User add a products and do checkout then SureTax Will calculate and adds a suretax lineitem to order and also saves log in watchdog.

-> Manual creation of Order will give suretax lineitem only if there is product in order page.
