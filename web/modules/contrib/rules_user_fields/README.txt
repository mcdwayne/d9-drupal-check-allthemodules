********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Rules User Fields
Author: Andrey Vitushkin <andrey.vitushkin at gmail dot com>
Drupal: 8
********************************************************************


INTRODUCTION
------------

This module extends the 'Rules' module for Drupal 8.
Today the 'Rules' module has the following restriction:
If you create a new 'User' in a 'Rule' then you can't get access 
to its custom fields in this 'Rule'.
This module solves this problem.
The example of using:
You can create new users with 'Rules' and populate their fields with
a data obtained using 'Webform' module.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
For further information visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

HOW TO USE
----------

The module enables to get access to all fields (including custom) of a User entity from Rules.
For doing this the module provides special Rule action called 'Get access to user fields'.
To get access to a user fields you need just add this action after creation of a new user.

Do the following:

1. Add 'Get access to user fields' action (you will find it in User section of the Rules actions list).
2. Click on 'Switch to data selection' button and select the entity of newly created user.
3. Save the Rule.

After that all fields of the selected User entity will be available from the data selector.
And you will can to use them in others actions of this Rule.
