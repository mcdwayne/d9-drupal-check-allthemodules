# LetsEncrypt

https://www.drupal.org/project/letsencrypt

## PHP LetsEncrypt client library integration.
https://github.com/yourivw/LEClient

PHP LetsEncrypt client library for ACME v2. The aim of this client is to make an easy-to-use and integrated solution to create a LetsEncrypt-issued SSL/TLS certificate with PHP. The user has to have access to the web server or DNS management to be able to verify the domain is accessible/owned by the user.

## NB!
Do not forget to remove `_account` when change acme-url `satage`/`prod`

## CODE usage:
```php
<?php
$domain = "example.org";
\Drupal::service('letsencrypt')->sign($domain);
\Drupal::service('letsencrypt')->read($domain);
```
