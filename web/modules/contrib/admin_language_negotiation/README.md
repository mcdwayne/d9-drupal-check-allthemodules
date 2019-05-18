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

The Admin Language Negotiation module allows non-admin
users to have their preferred administration language.

The module enables the *Administration pages language* settings to anyone
that has the permission *Admin language negotiation*
and introduces a new Language Negotiation based on this permission.

Actually the Drupal core only enables the *Administration pages language*
settings to users that are administrator, but in some
circumstances we need to specify a different administration
language for users that are not admin, for instance, site editors.

For example if your users only have administrative permissions
to edit nodes and a user wishes to have its backend always in English, 
he will not be able to specify an administrative language. 
The result is that if he visits an administrative page */de/node/1/edit* 
he will always be forced to see the page in 
German rather than his preferred administrative language.

REQUIREMENTS
------------

* The site must have at least two active languages.

RECOMMENDED MODULES
-------------------

INSTALLATION
------------

* Download the module via composer
* Enable the module via drupal console (recommended)

CONFIGURATION
-------------

* Assign the permission *Admin language negotiation* to a
role without full administrative permissions
* Visit */admin/config/regional/language/detection* and under 
**Interface text language detection** enable and move the detection method
*Account administration pages with user permission* to the top or in the 
order of selection that you wish

### Check that everything works as expected

Once the configuration is done we can run an easy test:

Open a user edit page (/user/{uid}/edit) of any user with 
the permission *Admin language negotiation* and you will notice under
the section *Language Settings* that the *Administration pages language*
dropdown is available, now, select a preferred language.

With the same user, try to visit a node edit page in a language different
from the one you selected in the step above. You should have all the 
administrative interface (buttons included) in your preferred language.

TROUBLESHOOTING
-------------

#### The *Administration pages language* dropdown doesn't appear under the user edit profile
Make sure the user has a role that containts the permission *Admin language negotiation*.

#### The administrative page is not in my preferred language
Make sure you enabled the language negotiator under */admin/config/regional/language/detection*
and that you ordered it on the top of the list.

#### The non administrative pages are not showing in my preferred admin language
That's correct. If you visit a node page view page in a different language, the
admin toolbar and any other text will be in the node language.
This because the behavior that this module provides only applies on admin routes.

NOTES
-----

Without this module, the *Administration pages language* is only visible if a user
has all available permissions (aka isAdmin() returns true).

MAINTAINERS
-----------

This module is sponsored by PwC's Experience Center - https://www.drupal.org/pwcs-experience-center

Current maintainers:
 * Alessio De Francesco (aless_io) - https://drupal.org/u/aless_io - https://github.com/aless-io
