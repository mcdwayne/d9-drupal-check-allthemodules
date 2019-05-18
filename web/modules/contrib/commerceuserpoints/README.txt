CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How It Works
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------

Commerce User Points module provides User Points functionality with Commerce.


REQUIREMENTS
------------
This module requires the following:
 * Commerce (and its dependencies) (https://drupal.org/project/commerce).


INSTALLATION
------------
 * Download module and put it into modules directory.
 * Enable module from Administration > Extend.


CONFIGURATION
-------------

 * Administration > Commerce > Commerce User Points Config
    1 User registration points (Points that user get on account registration).
    2 Percentage (User Points return on the purchase of a product based on 
    configured percentage).
    3 Advance Setting
    4 Day (Give the special discount on a specific day).
    5 The threshold value (Threshold limit for the user for using when 
    purchase).

HOW IT WORKS
------------

 * General considerations:
    1 When a user creates an account then he/she will get some predefined 
    points.

* Checkout workflow:
    1 At checkout process User points Redeem option to show.
    2 The user can use all usable points. (If thrash hold set then the user 
    can not be used thrash hold points example: usable points = total points
    threshold points.
    3 The user can also be used the specific number of points.
    4 Final price calculated after points apply. (final price = total price
     usable points).


TROUBLESHOOTING
---------------
 * No troubleshooting pending for now.


MAINTAINERS
-----------
Current maintainer:
 * Jigish Chuhan (jigish) - https://www.drupal.org/u/jigishaddweb

This project has been developed by:
 * Addwebsolution - https://www.addwebsolution.com
