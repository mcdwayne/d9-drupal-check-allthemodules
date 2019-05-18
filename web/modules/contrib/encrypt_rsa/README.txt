Encrypt RSA
================================================================================

Encrypt RSA provides public-keys (asymmetrical) encryption method plugins for the Encrypt module, using RSA algorithm . These plugins offer a variety of solution to use public/private key to encrypt - and send - your date safely.


Installation
------------

Install this module normally, either from a CLI tool (drush or drupal console) or from the Extend menu on Drupal admin UI.
Downloading this module from composer will automatically pull the required libraries. If you don't use composer for compiling your codebase, then either download and autoload the libraries manually OR use only OpenSSL Seal methods, which don't require anything other than openssl php extension.


Generate a RSA Key Pair
-----------------------

Although you can find online thousands of way for creating your keys, below the openssl CLI snippets:

Create the private key, protected by passphrase:
$ openssl genrsa -des3 -out private.pem 2048

Extract the private key:
$ openssl rsa -in private.pem -outform PEM -pubout -out public.pem

For more info: https://rietta.com/blog/2012/01/27/openssl-generating-rsa-key-from-command/



How to use this module
----------------------

PHP 5.6:
 - Create a private key without passphrase. From the snippet above just omit "-des3" option. Although private key without passphrase are less secure, we do ALWAYS suggest you upload to Drupal ONLY your public keys, and keep the private safely with you. This habit should keep you safe. Extract your public key.
 - Create a 'PEM Public' Key from Administration > Configuration > System > Keys, and copy-and-paste the content of your publick key (i.e. public.pem). Make sure you include "-----BEGIN[...]-----" and "----END[...]-----" block markers.
 - Create a 'Public EasyRSA' encryption profile from Administration > Configuration > System > Encryption profiles, and select the key you've just created.

PHP 7.x:
 - Create a private key, using the snippets above. Extract your public key.
 - Create a 'PEM Public' Key from Administration > Configuration > System > Keys, and copy-and-paste the content of your publick key (i.e. public.pem). Make sure you include "-----BEGIN[...]-----" and "----END[...]-----" block markers.
 - Create a 'Public OpenSSL Seal' encryption profile from Administration > Configuration > System > Encryption profiles, and select the key you've just created.


What about decrypting from within Drupal?
-----------------------------------------

If you need to encrypt AND decrypt within drupal, you are probably safer using symmetric encryption i.e. AES with Real AES drupal module.

But if you really want to encrypt and decrypt from within the same Drupal instance, then you can create a 'PEM Private' key and an encryption profile with any 'Private *' encryption method.

Make sure you understand the risks, also keep in mind we don't store the passphrase anywhere. You can pass the passphrase to the 'Private OpenSSL Seal' method by storing it in the State API. We let to you the task to find the value key name. :p
EasyRSA doesn't support private key with passcode.
