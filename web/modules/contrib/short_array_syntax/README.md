
Table of Contents
-----------------

* Introduction
* Usage
* Installation
* Notes
* References


Introduction
------------

Converts PHP's array() syntax to PHP 5.4's short array syntax\[\] using
[Thomas Bachem's](https://github.com/thomasbachem) 
[PHP 5.4 Short Array Syntax Converter](https://github.com/thomasbachem/php-short-array-syntax-converter).


Usage
--------

Execute `drush short-array-syntax example` to convert all PHP's array() syntax 
to PHP 5.4's short array syntax[] within `*.inc`, `*.install`, `*.module`, and 
`*.php` files.


Installation
------------

     # To install the 'short-array-syntax' globally use...
     drush pm-download "short_array_syntax" --destination=~/.drush;

     # To install the 'short-array-syntax' within a Drupal website use...
     cd DRUPAL_SITE;
     drush pm-download "short_array_syntax";


Notes
-----

- The `convert.php` script will automatically be downloaded from 
  <https://github.com/thomasbachem/php-short-array-syntax-converter> to the 
  'short_array_syntax' directory the first time this command is executed. 


References
----------

- [[Policy, no patch] PHP 5.4 short array syntax coding standards](https://www.drupal.org/node/2135291)
- [PhpStorm: Short hand syntax for arrays](http://typo3-development.nl/nl/on-the-side-table/phpstorm-short-hand-syntax-for-arrays/)



Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
