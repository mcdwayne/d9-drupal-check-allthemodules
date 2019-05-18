Commerce Shipping Econt
=================

Provides Econt(Bulgarian logistics provider) shipping functionality for Drupal Commerce.

## Setup

1. Before installing the module add your google maps api key to commerec_econt/config/install/commerce_econt.settings.yml
	(This will be fixed in the next release version of the module!)
	
2. Install the module.

3. In Commerce -> Configuration -> Shipping methods fill in your econt configuration settings and save.
If the system throws error on saving like 'Incorrect Default Store address. Please check it',
please check your Store Address in Commerce -> Stores(You need to fill the first address row with a street name
and the second one with a street number).