## Overview

The Sodium module for Drupal provides an encryption method for the Encrypt
module that allows symmetric encryption and decryption of data using the
Sodium (libsodium) software library. PHP integration is provided by the 
Halite library.

## Requirements

* PHP 5.6 or later
* [Sodium (libsodium) library](https://github.com/jedisct1/libsodium)
* [Libsodium PHP extension](https://github.com/jedisct1/libsodium-php)
* [Halite PHP library](https://github.com/paragonie/halite)
* [Encrypt module](https://www.drupal.org/project/encrypt)
* [Key module](https://www.drupal.org/project/key)

Whew! That's a lot of requirements.

It sounds more complicated than it actually is. Information about installing
the Libsodium library and the Libsodium PHP extension can be found in
["Using Libsodium in PHP Projects."](https://paragonie.com/book/pecl-libsodium)

The Halite PHP library should be installed using Composer, by employing
a tool such as Composer Manager or Composer Merge Plugin, or by adding
the Sodium module or Halite library to the require section of your project's
root composer.json file.

## Using Sodium in Encrypt

Once everything is installed and operational, do the following:

1. Generate a random 256-bit key
   * Option 1: Output your key to a file using a method such as the following:
      * `dd if=/dev/urandom bs=32 count=1 > /path/to/secret.key`
        (change the path and filename to suit your needs)
   * Option 2: Output your key to standard output and Base64-encode it so it
     can be copied and pasted:
      * `dd if=/dev/urandom bs=32 count=1 | base64 -i -`
2. Create a key definition using the Key module (at 
   /admin/config/system/keys/add)
   * Select "Encryption" for the key type
   * Select "256" for the key size
   * Select your preferred key provider
      * Select "File" if you output your key to a file in the previous step;
        do not check "Base64-encoded" unless you Base64-encoded the key
      * Select "Configuration" if you copied your key, rather than outputing
        to a file ("Configuration" is fine for development and testing, but
        please use something more secure in a production environment); paste
        the key value and check "Base64-encoded"
      * Select another, more secure option if you've installed additional
        providers
   * Click "Save"
3. Create an encryption profile using the Encrypt module (at 
   /admin/config/system/encryption/profiles/add)
   * Select "Sodium" for the encryption method
   * Select the name of the key definition you created in step 2
   * Click "Save"
4. Test your encryption by selecting "Test" under "Operations" for the
   encryption profile on the profiles listing page
   (/admin/config/system/encryption/profiles)
