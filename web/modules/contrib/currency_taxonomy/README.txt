CONTENTS OF THIS FILE
---------------------

 * INTRODUCTION
 * INSTALLATION
 * TROUBLESHOOTING (POSSIBLE PERFORMANCE ISSUES)
 * MAINTAINERS

INTRODUCTION
------------
This module creates currency taxonomy with ISO code and Country Name enlisted in
it. The module provides list of all available currencies. Each list term name
has value in the "Currency Name(ISO code)" format where ISO code is Alphabetic
ISO code of that currency.

INSTALLATION
------------
1. Check requirements section first.
2. Enable the module.
https://www.drupal.org/documentation/install/modules-themes/modules-7

TROUBLESHOOTING (POSSIBLE PERFORMANCE ISSUES)
-----------
Currency taxonomy module uses taxonomy_term_save() to add terms 
based on currency. taxonomy_term_save() will be run for each 
term - and this will give performance issues
on enabling module

MAINTAINERS
-----------
Current maintainers:

 * Neha Pandya(nehapandya55) - https://www.drupal.org/user/2848621
