CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Config Ignore Keys allows the developer to ignore particular keys in the
configuration and not whole configuration files.

This module allows for granularity and more control over what is ignored. There
are cases in which you would want to track a specific config file, but just
ignore one key. For example, the email address for contact forms during
development could be different than that of dev environments, but the rest of
the contact forms configuration should not.


 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/config_ignore_keys

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/config_ignore_keys


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Config Ignore Keys module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------


After installing the project, create a plugin as in this example:

``` PHP
namespace Drupal\contact_form_ignore\Plugin\ConfigIgnore;
/**
 * Class ContactFormIgnore.
 *
 * @ConfigurationIgnorePlugin(id = "contact_form_ignore")
 */
class ContactFormIgnore implements ConfigurationIgnorePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigurations() {
    return [
      'contact.form.contact_form' => [
        'recipients',
      ],
    ];
  }

}
```

The plugin must be added in the src/Plugin/ConfigIgnore folder of the module
implementing it.

The return value of the getConfigurations function has to be an array, with the
key the config name and the value each config key you desire to ignore.

MAINTAINERS
-----------

 * Rosian Negrean (prics) - https://www.drupal.org/u/prics

Supporting organization:

 * PitechPlus - https://www.drupal.org/pitechplus
