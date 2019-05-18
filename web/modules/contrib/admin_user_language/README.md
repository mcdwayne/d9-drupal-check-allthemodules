CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Notes
 * Maintainers
 
INTRODUCTION
------------

The Admin User Language module makes sure that any user
has a pre-selected *administration pages language*.

This is useful when you want to give a consistent user experience 
to your users, in the specific, when you want to force the back end 
navigation to be always in a specific language (e.g. English).

If your users have any administration privilege in fact, since their registration,
they will have the *administration pages language* set to *- no preference -*
unless manually changed.
 
This module makes sure that on every user registration/update, its administration
language will always be set to a specific one of your choice. Further, it can also 
be forced to avoid arbitrary user customisation.

REQUIREMENTS
------------

* The site must have at least two active languages.

RECOMMENDED MODULES
-------------------

* admin_language_negotiation - Used in conjunction with this module 
will provide a complete set of tools to manage the user's administration 
language experience.

INSTALLATION
------------

* Download the module via composer
* Enable the module via drupal console (recommended)

CONFIGURATION
-------------

* Navigate to */admin/config/admin_user_language/settings*
* Select the *Default language to assign*
* Tick the checkbox *Force language* only if you don't wont to allow
an arbitrary *administration pages language* by your users

### Check that everything works as expected

Once the configuration is done we can run an easy test:

Register a new user and verify that its *administration pages language*
is set to the one you defined in the configuration.

If you selected *Force language*, try to change the *administration pages language*
to a language that is different from the one you configured, then save the user.
Open the user profile and check if the *administration pages language* has been
re-set to the one you specified in the configuration.

TROUBLESHOOTING
---------------

#### The *Administration pages language* dropdown does not appear under the user edit profile
Make sure the user has you are editing has the necessary administration privileges.

#### The administrative page is not in my preferred language
You will need another module to address this issue, install *admin_language_negotiation*.

NOTES
-----

This module does not display the *administration pages language* selection for users
that don't have the right privileges. To achieve so you need to look to a user
with enough administration permissions or by installing the module *admin_language_negotiation*.

MAINTAINERS
-----------

This module is sponsored by PwC's Experience Center - https://www.drupal.org/pwcs-experience-center

Developed by:

 * Alessio De Francesco (aless_io) - https://drupal.org/u/aless_io - https://github.com/aless-io
 
Current maintainers:

 * Alessio De Francesco (aless_io) - https://drupal.org/u/aless_io - https://github.com/aless-io
