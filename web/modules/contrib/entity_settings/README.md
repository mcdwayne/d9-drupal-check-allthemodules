CONTENTS OF THIS FILE
---------------------
   
* Introduction
* Requirements
* Installation
* FAQ
* Known Issues
* Maintainers
 
## INTRODUCTION

The Entity Settings module provides the ability to store settings for
entities in fields. The settings can then be employed by a theme, either
in a preprocess function or a Twig template, to alter HTML markup or CSS
classes based on the settings values. For referenced entities, settings
are available for ancestors, though this currently only works for
Paragraph entities.

The module currently defines three field types for storing setting values:

* **Setting List (text),** which is similar to the _List (text)_ field
type provided by the Options module
* **Setting List (integer),** which is similar to the _List (integer)_
field type provided by the Options module
* **Setting Boolean,** which is similar to the _Boolean_ field type

The field types do not allow multiple values to be set.

## REQUIREMENTS

The only requirements to use this module are two modules provided by
Drupal core: Field and Options.

## INSTALLATION

Install the module in the usual manner, either on the site's Extend page
or with Drush.
 
## FAQ

### How to define a setting field

Add a field to an entity, such as a node, user, or paragraph, selecting
one of the setting fields as the field type. Setting fields are
organized under the _Setting_ heading in the _Add a new field_ select
list. Follow the steps to configure the field as usual, entering any
allowed values. The name of the field is prefixed with _setting__,
instead of _field__.

### How to pass settings through to a theme template

Implement a preprocess hook for the type of template for which the
settings are needed. Retrieve the settings by passing the entity to
EntitySettings::getSettings. For example, add this line to the top of a
theme's THEMENAME.theme file:
```
use Drupal\entity_settings\EntitySettings;
```
then implement hook_preprocess_HOOK as follows to make settings
available for all nodes:

```
function THEMENAME_preprocess_node(&$variables) {
  if (isset($variables['node'])) {
    $variables['settings'] =
EntitySettings::getSettings($variables['node']);
  }
}
```

Settings will now be available in node Twig templates in ```settings```.
For example, if a field allowed the user to select the name of a color,
it could be used in the template by accessing ```settings.0.color```.
Ancestor settings can be used by incrementing the number according to
the depth of the nesting. The first two levels (0 and 1) have aliases
(self and parent), so they could be used as follows:
```settings.self.color``` ```settings.parent.color```.

## KNOWN ISSUES

* Nested settings are only available for paragraph entities.

* When creating a setting field, the machine name displayed has the
usual _field__ prefix, instead of _setting__, though the latter will be
the actual prefix that is used upon saving.

## MAINTAINERS

Current maintainer:
* Rick Hawkins (rlhawk) - https://www.drupal.org/user/352283
