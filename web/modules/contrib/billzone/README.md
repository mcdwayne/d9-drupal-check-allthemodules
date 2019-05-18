Billzone module
===============

This module integrates the Billzone invoice handling system's API (https://www.billzone.eu) to Drupal 8.
With this module you can easily generate invoices and download them if it is needed.

The module provides a billzone Drupal 8 service, which has 2 public method:
* createInvoice($invoice)
* downloadInvoice($invoice_number)

You can see an example to use it in the billzone_example module.

This module also provides a Billzone settings form:
/admin/config/system/billzone (Configuration -> Web services -> Billzone settings)

In the settings form you can set the following properties:
* (*) Mode: You can choose between Sandbox and Live mode
* (*) Default unit identifier
* (*) Default account block prefix
* Invoice description
* Notes
* (*) Security token
* (*) Payment deadline

(* (asterisk) means that the corresponding property is required for the usage)

Sandbox mode
============

Billzone offers a sandbox mode, which is almost identical with the live mode, but in sandbox mode you can test your
application without any risk. This should be used for development.

Here you can register for a sandbox user:
https://sandbox.billzone.eu

How to start
============

You have to start filling in information in the Billzone system:
* Unit list: https://sandbox.billzone.eu/HU/hu/Pages/Company/UnitList.aspx
* Account numbers: https://sandbox.billzone.eu/HU/hu/Pages/Company/AccountBlockList.aspx
* Allowed IP addresses and security token: https://sandbox.billzone.eu/HU/hu/Pages/Company/Policy.aspx
* etc...

Common mistake
==============

The Billzone system returns with an 61003 error code.
This can be caused by 3 things:
* You forget fill in the Billzone settings form here: /admin/config/system/billzone
* You fill in wrong security key
* You forget that you IP is not in the allowed IP addresses. You can set it here: https://sandbox.billzone.eu/HU/hu/Pages/Company/Policy.aspx

Creator:
========

This module is created by David Czinege (david.czinege) at Creativenet Ltd.