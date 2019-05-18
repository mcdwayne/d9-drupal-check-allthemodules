Form Defaults
http://drupal.org/project/formdefaults
================


DESCRIPTION
------------
Ever want to add help text to a form in Drupal? Don't like a field title chosen by the developer? Want to change the description of a form field? Want to move a field to the top or bottom of a form? Want to do all of these things without any PHP code? This is your module.

Form Defaults will let you alter the defaults associated with a form, enable the forms module below and look for the [edit] controls in the description of each form field. Navigate to the form and click [edit] link on the field you want to edit. You'll be allowed to edit field titles, markup fields as well as the textual descriptions with each field.


REQUIREMENTS
------------
Drupal 8.x


INSTALLING
------------
1. Copy the 'formdefaults' folder to your modules directory.
2. Go to Manage > Extend > Modules. Enable the module.


CONFIGURING AND USING
---------------------
1. Go to Manage > People > Permissions. Under line 'Change form labels and text' set appropriate permissions. Usually only administrators should have access to change form labels.
2. Go to Manage > Structure > Form labels and text. Click on enable button.
3. Make sure you're log-in as a administrator (user #1). Go to any input form. For example the Node Edit tab. Click on appropriate [edit] link beside a field.
4. On the next page you can check HIDE THIS FIELD checkbox. This will hide that field from unauthorised users. You can also edit the FIELD TITLE. And you can edit the FIELD DESCRIPTION. When done click on SAVE button.
5. To preview the final result log-out. Then log-in as a user without permission to 'change form labels'.


REPORTING ISSUE. REQUESTING SUPPORT. REQUESTING NEW FEATURE
-----------------------------------------------------------
1. Go to the module issue queue at http://drupal.org/project/issues/formdefaults?status=All&categories=All
2. Click on CREATE A NEW ISSUE link.
3. Fill the form.
4. To get a status report on your request go to http://drupal.org/project/issues/user


UPGRADING
---------
1. One of the most IMPORTANT things to do BEFORE you upgrade, is to backup your site's files and database. More info: http://drupal.org/node/22281
2. Disable actual module. To do so go to Administer > Site building > Modules. Disable the module.
3. Just overwrite (or replace) the older module folder with the newer version.
4. Enable the new module. To do so go to Administer > Site building > Modules. Enable the module.
5. Run the update script. To do so go to the following address: www.yourwebsite.com/update.php
Follow instructions on screen. You must be log in as an administrator (user #1) to do this step.

Read more about upgrading modules: http://drupal.org/node/250790
