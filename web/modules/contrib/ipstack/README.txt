INTRODUCTION
------------

This module allows site developers to use ipstack.com API.

Usage:
use Drupal\ipstack\Ipstack;

$ipstack = new Ipstack($ip, $options);
$data = $ipstack->getData();

or

$ipstack = new Ipstack();
$ipstack->setIp($ip);
$ipstack->setOptions($options);
$data = $ipstack->getData();


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   Visit: https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Configure at: [Your Site]/admin/config/system/ipstack.
You need set your Ipstack Access Key. You can get the key from the
https://ipstack.com .

* Test at: [Your Site]/admin/config/system/ipstack/test


MAINTAINERS
-----------

Current maintainers:
 * Sergey Loginov (goodboy) - https://drupal.org/user/222910
