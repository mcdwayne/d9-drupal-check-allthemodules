CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developers
 * Maintainers


INTRODUCTION
------------

The Autocomplete Search Suggestions module provides auto-complete search
suggestions. The module is compatible with standard Drupal search, search views
and Apache SOLR, but not dependent on any of them.

Included in this module is a suggestion search block to replace the standard
Drupal search block, (which I found hard to target). If you use the suggestion
search block and the default settings, then both the block and the advanced
search will employ auto-complete.

 * For a full description of the module visit:
   https://www.drupal.org/project/suggestion

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/suggestion


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Autocomplete Search Suggestions module as you would normally install
a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
further information.


CONFIGURATION
-------------

    1. After the module is installed, enable the module.
    2. Flush the site cache.
    3. Go to Administration > Configuration > Suggestion and configure the
       suggestion settings.
       Note: there must be node content present before you can modify the
       settings.
    4. After configuration has been saved, index suggestions.
    5. Optionally, the Suggestion Search block can be enabled.


DEVELOPERS
-------------

Developers can enable auto-complete on any form by implementing
hook_form_FOM_ID_alter() and munging the following code snippet:
```php
function hook_form_FORM_ID_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['FIELD_NAME']['#autocomplete_route_name'] = 'suggestion.autocomplete';
  $form['#submit'][] = 'suggestion_surfer_submit';
}
```


MAINTAINERS
-----------

 * bkelly - https://www.drupal.org/u/bkelly
