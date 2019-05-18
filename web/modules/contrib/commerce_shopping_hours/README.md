# Commerce Shopping Hours

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Let's say that for some reason your shop is not open 24/7. Maybe you have a
restaurant with delivery that works only from 9AM to 23PM. In this case you
don't want to accept orders when the shop is closed. This module allows you to
define opening and closing hours for each day of the week. Your customers will
still be able to add items to their cart but when they try to checkout they will
be redirected to the warning page that displays custom message and shopping
hours. You can also add a block which displays message and shopping hours, so
that your customers know in advance that your shop is closed.

## REQUIREMENTS

To use this module you must have Drupal Commerce 2.x installed.

## INSTALLATION

1. Install module as usual via Drush, Drupal UI or Composer.
2. Go to "Extend" and enable the Commerce Shopping Hours module.

## CONFIGURATION

After you install the module go to
'/admin/commerce/config/commerce_shopping_hours' and define shopping hours for
your shop and enter the message you want to show to customers when your shop is
closed. You can also enable option to show shopping hours on the warning page.
If you want, you can also add a block which displays a warning message and
shopping hours. To do this go to the Block layout and add a block with the name
'Commerce Shopping Hours' to the region you want.

### AUTHOR

Goran Nikolovski  
Website: (http://www.gorannikolovski.com)  
Drupal: (https://www.drupal.org/u/gnikolovski)  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: (http://www.studiopresent.com)  
Drupal: (https://www.drupal.org/studio-present)  
Email: info@studiopresent.com  
