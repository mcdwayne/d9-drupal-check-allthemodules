********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Rules Data Exchanger
Author: Andrey Vitushkin <andrey.vitushkin at gmail dot com>
Drupal: 8
********************************************************************


INTRODUCTION
------------

This module extends the 'Rules' module for Drupal 8.
The module enables to exchange data between Rules and rules Components.
It's possible to store any data of a some Rule and then use them in others Rules or Components.
This can be used, for instance, for implementation of condition expressions like 'if..else' in a rule.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
For further information visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

HOW TO USE
------------

------------
 Store data
------------
To store rules data for using in components or other rules do the following:

1. Add 'Store data' action to your rule 
   (you will find it under 'Data' section of the actions list).

2. Select data to be stored.
   To do this switch to the 'Data selection' mode 
   (click on 'Switch to data selection' button).
   Then select data to be stored using the selector.

3. Think of a name for the variable in which a data will be stored.
   For that any string of text may be used.
   For instance, if the name of stored data will be:
   my stored data
   then this data will become available in the selector under the following name:
   @stored_data:my stored data

4. Type this name into the 'NAME' field and click on 'Save' button.

After that this data will become available in other rules or components.

-------------------
 Alter stored data
-------------------
You can alter the value of stored data with 'Set a data value' action.
For instance, you can select from the selector the following variable:
@stored_data:my stored data
and set to it a new value using 'Set a data value' action.
After that, a new value will be available in a current rule.
But remember that if you need that this new value will become available in other rules you need to store it again.
Just add 'Store data' action again and select our data:
@stored_data:my stored data
then type into the 'NAME' field the name of our data:
my stored data
After that, the new value will also be available in other rules.

-------------------
 Clear stored data
-------------------
Keep in mind that stored data are also available for other modules.
Therefore, if you work with a confidential data you may want to clear them after using.
The 'Clear stored data' action exist for this purpose.
To clear stored data do the following:

1. Add 'Clear stored data' action to your rule (you will find it under 'Data' section of the actions list).

2. Select data to be cleared.
   To do this switch to the 'Data selection' mode (click on 'Switch to data selection' button).
   Then select data to be cleared using the selector.
   For instance, if we want to clear stored data named as 'my stored data' then we need to select the following variable:
   @stored_data:my stored data

3. Click on 'Save' button.

Immediately upon completion of this action the stored data will be cleared.
In other words, after completion of this action the variable '@stored_data:my stored data' will become empty.
