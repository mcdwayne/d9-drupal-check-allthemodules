Commerce PayTabs

Issues:
-------
Unfortunately, the cancel link on the pay page of PayTabs does not
cancel payments when redirected to Drupal commerce. It needs work.

Description
-----------
This module provides integration with the PayTabs payment gateway.

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration

INTRODUCTION
------------
This project integrates PayTabs online payments into
the Drupal Commerce payment and checkout systems.

REQUIREMENTS
------------
This module requires no external dependencies.
But make sure to enable the 'Telephone' core module.

INSTALLATION
------------
* You can install this module via Composer, or
* clone it from drupal.org Git repo, or
* Download the module from D.O and install it the usual way:
   - Place it in the /modules or /modules/contrib directory
   - Go to 'Extend' as an administrator, and
   - Enable the module

CONFIGURATION
-------------
* Create new PayTabs payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Provide the following settings:
  - Merchant email.
  - Secret key.
