GoPay for Drupal
================

Instalation
-----------

Add repository for external library `gopay-php-api` from GoPay community into your `composer.json`.

```$yml
  "repositories": {
    ...,
    "GoPaySDK": {
      "type": "package",
      "package": {
        "name": "gopay/payments-sdk-php",
        "version": "1.3.2",
        "type": "library",
        "source": {
          "url": "https://github.com/gopaycommunity/gopay-php-api",
          "type": "git",
          "reference": "v1.3.2"
        }
      }
    },
    ...
```

Then running `composer require gopay/payments-sdk-php` will do the trick.
