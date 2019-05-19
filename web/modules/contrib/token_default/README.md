CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Token Default module allows a token to be given a default value in the
event of no value being found.

This can be altered per content type.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/token_default

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/token_default


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Token Default module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Search and metadata > Token
       Default to create and configure token defaults.
    3. Add a default token, include the label, the token string pattern the
       token replacement pattern and the content type to attach it to.
    4. Save.


KNOWN ISSUES
------------

 * There are currently no restrictions on what can be entered for the token
   pattern string, validation is required to restrict this to a single valid
   token pattern.
 * There are not currently any tests written for this module.
 * Currently a single content type must be selected, this restriction could be
   lifted or it could be applicable to multiple content types.
 * There is not currently a way to restrict this to the context of the
   replacement, for example pathauto.


MAINTAINERS
-----------

Supporting organizations:

 * Numiko - https://www.drupal.org/numiko
