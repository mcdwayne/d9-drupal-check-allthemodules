# PHPSecLib Encryption for the Encrypt module

This module provides an EncryptionMethod plugin for the Drupal 8 Encrypt module.
It uses the third party [PHP Secure Communications Library](https://github.com/phpseclib/phpseclib) to provide AES encryption.

## INSTALLATION

1. Download the library via Composer in the root directory of Drupal:
    * `composer require phpseclib/phpseclib:"^2.0.0"`
2. Download and install encrypt_seclib module.
   See https://www.drupal.org/documentation/install/modules-themes/modules-8
3. Run "composer drupal-update" to install the PHPSecLib library.
4. Go to /admin/config/system/encryption and select the Encrypt "PHP Secure
   Communications Library (phpseclib)" encryption method when creating a new
   encryption profile.
