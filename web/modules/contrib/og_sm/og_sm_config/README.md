# Organic Groups : Site Configuration
Module to support site specific settings by storing them in a separate table and
link them to the Site they belong to.


## Functionality
This module provides configuration overrides, site configuration should be 
fetched just like normal configuration, config overrides will be loaded based on
the current site context.

Site configuration is mostly based on the config override system, the most
important difference is that it uses a custom config storage table, this prevents
the configuration from being exported/imported.



## Requirements
* Organic Groups



## Installation
1. Enable the module.



## API
### Site configuration form.
To store configuration within a site context the configuration form should be
extended from the `SiteConfigFormBase` class.
Using the form with a route that allows determining site context will result in
configuration saved under that site context. See `og_sm_config_test` module for a
simple implementation.


### Get a site configuration object.
```php
$site_config = OgSmConfig::getOverride($site, 'site_settings');
$site_path = $site_config->get('path');
```
