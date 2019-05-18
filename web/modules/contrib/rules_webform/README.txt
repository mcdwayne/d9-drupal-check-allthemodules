  
    D R U P A L    M O D U L E



               _o__o_
               /\  /\
____________RULES WEBFORM____________
 |         |             |         |          
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~



INTRODUCTION
------------
The 'Rules Webform' module provides integration of 'Rules' and 'Webform' modules for Drupal 8.
It enables site builders and administrators to get access to webform submission data from rules.
Also it provides possibility of altering and removing webform submissions from rules.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. For further information visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules


HOW TO USE
---------------------------
 Read a webform submission
---------------------------

To access webform submission data from a rule you need to do two things:

1. Select the 'Webform submit' event from the 'React on event' listbox.
2. Select necessary webform from the 'Webform' listbox that will appear below.

After that will be available two new variables: 'webform_fields' and 'webform_info'. 
You can use them in your rule actions and conditions.
'webform_fields' contains values of webform fields and 'webform_info' contains additional information like submission date, author and so on.

To investigate them it's conveniently to use 'Data selection mode'. Therefore click on 'Switch to data selection mode' button in your condition or action page. Then type variable name with dot at the end, like this: 'webform_fields.' After that you will see all webform fields and you can choose any of them. But you can also use 'Direct input mode'. For instance, if you need to get the value of 'message' field you can use Twig syntax like this: {{ webform_fields.message }}

----------------------------
 Alter a webform submission
----------------------------

To alter a webform field value you need to do the following:

1. Add 'Set webform field value' action.

2. Select a webform field you want to alter.
Keep in mind that it's possible to select a webform field only in 'Data selection' mode.
Therefore click on 'Switch to data selection' button before you start typing.
Then type the name of a necessary webfrom field.
For instance, if you want to alter the value of 'message' field, type the following:
webform_fields.message

3. Input a new value of webform field into the 'VALUE' field.
Remember that you can completely replace field value as well as to complement existing value. Let's say you want to complement the value of field 'name' with 'Sir'. Then stay in the 'Direct input' mode and type the following:
Sir {{ webform_field.name }}

-----------------------------
 Delete a webform submission
-----------------------------

To delete a webform submission from a rule use 'Delete webform submission' action.

KNOWN ISSUES
------------
Before adding 'Delete webform submission' action ensure that you save previous changes of your rule
(click on 'Save' button).

AUTHOR
------------
Andrey Vitushkin (wombatbuddy) - https://www.drupal.org/u/wombatbuddy
email: andrey.vitushkin at gmail com
