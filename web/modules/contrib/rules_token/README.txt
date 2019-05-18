********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Rules Token
Author: Andrey Vitushkin <andrey.vitushkin at gmail dot com>
Drupal: 8
********************************************************************


INTRODUCTION
------------

The module enables to use in Rules tokens provided by the following modules:
 'Token'
 'Custom Tokens'
 'Custom Tokens Plus'

It allows getting values of any Drupal data and use them in Rules.
You can get the current date, site URL, webform submissions and so on.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/rules_token

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/rules_token

REQUIREMENTS
------------

This module requires the following modules:

 * Rules (https://www.drupal.org/project/rules)
 * Token (https://www.drupal.org/project/token)

RECOMMENDED MODULES
-------------------

 * Rules Data Exchanger (https://www.drupal.org/project/rules_data_exchanger):
   When enabled, it's possible to store a data of a some rule and then use them in others rules or components.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
For further information visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

CONFIGURATION
-------------

The module has no menu or modifiable settings.
There is no configuration.
Before you uninstall the module, remove from rules all conditions and actions provided by this module.

HOW TO USE
------------

The module provides one action, named 'Get token value'.
And it provides two conditions, named 'Compare Data with Token' and 'Compare Token with Token'.
They are easy to use.
You just need to remember that there are two types of tokens:

1. Tokens that related with a context of a current rule event.
   The examples of such tokens are:
    [node:],
    [user:],
    [webform_submission:]

   For such tokens you need to specify the entity to which they are intended.
   The field named 'ENTITY OF TOKEN' provided for that.
   You have to switch to the 'Data selection mode'
   (click on the 'Switch to data selection' button).
   And then select the relevant entity using the selector.

2. Global tokens that not related with a context.
   The examples of such tokens are:
    [date:],
    [url:],
    [random:]

   For those tokens you don't have to specify any entity.
   Therefore, you should leave the 'ENTITY OF TOKEN' field empty.

--------------------------
 'Get token value' action
--------------------------
Let's say you want to get the value of a webform field after a webform submitted.
For instance, the name of the webform is 'Contact' and the name of the field is 'Message'.
To achieve the goal to do the following:

1. Install the 'Webform' module.

2. Create the new rule to react on 'After saving a new webform submission' event.
   You will find it under 'Webform submission' section of the event list.
   (be warned, that the 'Rules Webform' module can hide this event).

3. Add the 'Get token value' action
   (you will find it under the 'Data' section of the action list).

4. Click inside the 'TOKEN' field to specify the place for the token insertion.

5. Then click on the 'Browse available tokens.' link
   (you will find it under the 'TOKEN' field).

6. Wait to see the window with the available tokens list.
   Find the [webform_submission:values:?] token from the appeared window.

7. Click on this token.
   After that, this token will be inserted into the 'TOKEN' field.

8. Replace the question mark in the token with the webform field key.
   As a result our token will change to: [webform_submission:values:message]

9. Type the token entity into the 'ENTITY OF TOKEN' field.
   To do this click on the 'Switch to data selection' button
   (you will find it under the 'ENTITY OF TOKEN' field).
   Then select the 'webform_submission' variable from the selector.
   After that the 'ENTITY OF TOKEN' field will contain the following value:
   webform_submission

10. Click on the 'Save' button and save the rule.

After that, the new 'token_value' variable will be accessible in the data selector.
When the rule run, this variable will contain the value of our token.
And you are free to use it in others actions of the rule.

If you need to get the current date or site URL, then select the tokens,
for instance, [date:html_date] and [site:url-brief]
and leave the 'ENTITY OF TOKEN' field empty.

-------------------------------------
 'Compare Data with Token' condition
-------------------------------------
For example, you want to compare the current date with a some value.
For that you should to do the following:

1. Add the 'Compare Data with Token' condition
   (you will find it under the 'Data' section of the condition list).

2. Input the value to be compared into 'Data' field.

3. Click on the 'Browse available tokens.' link
   (the link is located under the 'TOKEN' field).

4. Select the appropriate token, for instance, [date:html_date]

5. Because we used the global token, leave the 'ENTITY OF TOKEN' field empty.

6. Click on the 'Save' button.

--------------------------------------
 'Compare Token with Token' condition
--------------------------------------
Use this condition if you need to compare the values of two tokens.
