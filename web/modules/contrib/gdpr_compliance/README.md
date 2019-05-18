CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Try To Keep It Simple
The General Data Protection Regulation Compliance module provides
basic GDPR Compliance use cases.

 * For a full description of the module visit:
   https://www.drupal.org/project/gdpr_compliance

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/gdpr_compliance


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the GDPR Compliance module as you would normally install
   a contributed  Drupal module. Visit https://www.drupal.org/node/1897420
   for further information.


DEVELOPMENT
-------------
1. HOOK Policy Page ALTER (see Drupal\gdpr_compliance\Controller\PagePolicy::page().
 * `&$policy` - inline template
 * `&$context` - template data: `changed`, `mail`, `url`

```
/**
 * Implements hook_gdpr_compliance_policy_alter().
 */
function HOOK_gdpr_compliance_policy_alter(&$policy, array &$context) {
  $context['mail'] = 'mail@example.org';
}

```


MAINTAINERS
-----------

 * Anatoly Politsin (APolitsin) - https://www.drupal.org/u/apolitsin

Supporting organization:

 * Synapse-studio - https://www.drupal.org/synapse-studio
