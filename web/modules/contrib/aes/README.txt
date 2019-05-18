
WHAT THIS MODULE IS GOOD FOR
----------------------------
This module is provide simple general purpose AES encryption API to use in other modules.

In Drupal 6.x and 7.x it was also used for making your users passwords readable by admins. Since Drupal 8.X this
functionality was removed from the module to be implemented as a separate component/module.

REQUIREMENTS
----------------------
This module requires an implementation of AES encryption to work. Since 1.4 there are two supported implementations:
1. PHP's Mcrypt extension.
2. PHP Secure Communications Library (phpseclib)

You need to have at least one of these installed (both is fine as well).
Mcrypt is a lot faster than phpseclib (about 60 times faster according to my unscientific testing), so you might want
to use Mcrypt if you have it. But if you don't, then phpseclib is a great alternative, and the speed difference probably
won't matter in most cases.

If you don't have any of them, then read the next section below.

Also note that although this module SHOULD work on Windows, it has only been tested on Linux.

Drupal 8 version is required presence of 'active' configuration in setings.php file. Having configuration in file going
to be deprecated one day, but so far this works nicely. Just add to your config file something like
  $config_directories['active'] = '/path/to/store/keys/outside/webroot';

It is assumed keys and other related info should not be stored keys in the database.

HOW TO GET AN AES IMPLEMENTATION
----------------------
If you don't have an AES implementation (you'll notice this when you install this module) then the easiest
implementation for you to get is probably the PHP Secure Communications Library (phpseclib).

To work with PhpSecLib you need to use it with Library module.

That's it! Try installing/enabling the module again.

If you want to use the Mcrypt implementation instead then you can find information on how to install it
here: http://php.net/mcrypt . Note that you most likely need to be running your own webserver in order to install
Mcrypt. If you're on a shared host you'll probably have to ask your hosting provider to install Mcrypt for you (or use
phpseclib instead).

ALTERNATIVE ENCRYPTION
----------------------
You may use your own encryption with this module. To do so, define a Plugin based on examples provided. Detailed
information is in the API.txt file .
