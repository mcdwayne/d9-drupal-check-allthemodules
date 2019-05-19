SMS ROUTESMS
----------------

INTRODUCTION
------------

The SMS RouteSMS module provides integration to SMS Framework for the RouteSMS
gateway. It allows the users of SMS Framework module to send SMS using RouteSMS
as a gateway.

REQUIREMENTS
------------

This module requires the SMS Framework (https://drupal.org/project/smsframework)
module.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-7 for further
information.

CONFIGURATION
-------------

To use this module:
1) Enable it at Admin >> Modules (/admin/modules)
2) Navigate to Admin >> SMS Framework >> Gateway Configuration, then click on
   the "configure" link next to RouteSMS Gateway
   (/admin/smsframework/gateways/routesms)
3) Fill in your api details as provided you by RouteSMS
4) Fill in a mobile number (full international code without the '+') to which a
   confirmatory SMS will be sent.
5) Click "Save"
6) Set RouteSMS as your default gateway at
   Admin >> SMS Framework >> Gateway Configuration
   (/admin/smsframework/gateways)
7) Test sending messages.

MAINTAINERS
-----------

Current maintainers:
 * almaudoh - https://drupal.org/user/488082
