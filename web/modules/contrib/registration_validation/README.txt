CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------
The Registration Validation module allows site administrators to configure
custom validation rules for the user registration form. Why would we need this?
There are plenty of modules available to thwart spam bots from successfully
registering an account on Drupal website installations, but human spammers are
a breed of their own. This module was created to add additional validation
rules for the most commonly known human spammers at one of our client's website.


 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/registration_validation


 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/registration_validation


REQUIREMENTS
------------
This module currently has no requirements beyond Drupal core.


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.


CONFIGURATION
-------------
 * Configure user permissions in Administration » People » Permissions:

   - Administer registration validation rules

     This permission is required to perform administration tasks for how data
     should be validated when a new user creates an account using the user
     registration form.

 * Customize the validation settings at
   admin/config/people/registration-validation.


TROUBLESHOOTING
---------------

 * If the default error message does not appear and validation fails to work as
   expected, check the following:

   - Are there any email domain names entered in the "Blacklisted email
     domains" textarea and are they each on separate lines?

   - Are there any strings entered in the "Blacklisted username strings"
     textarea and are they each on separate lines?

 * If the above are true, try saving the form again and retest validation.


FAQ
---

 Q: The only options available is to validate the user's email domain and/or
    username, and set error messages to display when validation fails. Will
    there be more rules available in the future?

 A: Yes, this is a very young project that was initially created for a client.
    We do plan to add additional functionality in the future, and are seeking
    co-maintainers to join our project.


MAINTAINERS
-----------

Current maintainers:
 * Richard C Buchanan, III (Richard Buchanan)
   - https://www.drupal.org/u/richard-buchanan
