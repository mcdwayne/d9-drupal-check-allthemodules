
# Webpay Module

This module helps with the integration and makes possible to build the specific
module for the specific type of commerce (drupal commerce, ubercart, ...other)

A test connection feature can help you to understand how it works the integration.

# Requirements

This module require some php extensions
* soap
* mcrypt

# Dependencies
* Transbank Web Services SDK (Include in composer.json): https://github.com/freshworkstudio/transbank-web-services

# Installation

Its recommend to download this module with composer https://getcomposer.org/.

# Considerations

This module just perform conection to Webpay in dev or prod enviroments. On this
last case the commerce site must get a key from Transbank and after a (long)
period of certification the site will be ready.
