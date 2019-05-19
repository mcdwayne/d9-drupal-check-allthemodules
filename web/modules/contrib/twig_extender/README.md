CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Twig Extender module adds a simple plugin system to add new twig extensions
(Filter and Functions). Provides a new service provider for "twig.extensions" to
add new plugins.

 * For a full description of the module visit:
   https://www.drupal.org/project/twig_extender

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/twig_extender


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.
 * Drupal 8.2 or lower: Use Twig Extender 8.1.
 * Drupal 8.3 or higher: Use Twig Extender 8.2.



INSTALLATION
------------

 * Install the Twig Extender module as you would normally install a contributed
 Drupal module. Visit https://www.drupal.org/node/1897420 for further
 information.


CONFIGURATION
--------------

**Function: Create Block**
Using for creating a block configuration on the fly

```
{{ block_create('plugin_id', [<plugin-config>]) }}
```

**Function: View Block**
Using a existing block configuration
```
{{ block_view('config_entity_id') }}
```

**Function: Is user logged in**
```
{% if user_is_logged_in() %}
  Hello user
{% else %}
  Please login
{% endif %}
```

**Function: Is front**
```
{% if is_front() %}
On frontpage
{% endif %}
```

**Filter: To url**
```
{{ node|to_url }}
{{ urlObject|to_url }}
```

For more information visit:
https://github.com/b-connect/twig_extender


MAINTAINERS
-----------

 * Erik Seifert - https://www.drupal.org/u/erik-seifert

Supporting organization:

 * b-connect GmbH - https://www.drupal.org/b-connect-gmbh
