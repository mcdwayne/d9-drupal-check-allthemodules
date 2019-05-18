# Icons
This module provides an API and render elements to use icons within Drupal 8.
It defines a configuration entity type for icon sets and a plugin type for Icon
Libraries.

But to make sure everybody won't start empty handed, a set of sub modules is
included to integrate icons Icomoon and Fontello from the start.


## CONTENTS OF THIS FILE

 * Requirements
 * Installation
 * Configuration
 * Maintainers

## Requirements
This module requires the following core modules:
 * Options
  

## Installation
Just enable the core Icons module and one of the icons submodules (Icons 
Fontello and Icons Icomoon), or create/supply your own. You will need at least
one Icon Library plugin to create an Icon Set configuration.

## Configuration
When enabling the module, choose which submodule you want to use, or provide
your own plugin for a custom icon library. For now icon sets from fontawesome
for example is not supported, but it is pretty easy to do this based on the
submodule fontello or icomoon.

After enabling the module(s) you can define an icon set from the interface.
Just go to: admin/appearance/icon_set

Choose "Add an icon set". Choose a name and the library plugin for icons.
And fill in the settings needed for the specific plugin. For instance, fontello
and icomoon require you to specify a local path to the folder where the css,
json and fonts are located.

After doing this you can use icons from the interface for Menu Link Content and
Views Menu Links. Which means you can add icons from a menu link content entity
that a some user creates within drupal. Or when you manage a view page display,
you could configure your menu item for the view to have an icon.

Beware!! Using multiple icon sets, especially those based on the same CSS could
give conflicts in naming and styling. So when generating these sets make sure
the class prefixes in css for instance are different between those sets if you
use multiple sets on a single page.

To use an icon through a custom render array is pretty easy as well.
Just build a render array like this:

```php
$icon = [
  '#type' => 'icon',
  '#icon_set' => 'icon_set_configuration_entity_id',
  '#icon_name' => 'icon_name',
];
```

Or you could use a combination of the configuration entity id with the icon
name like this:

```php
$icon = [
  '#type' => 'icon',
  '#icon_id' => 'icon_set_configuration_entity_id:icon_name',
];
```

## Maintainers
Current maintainers:
 * Jeffrey Bertoen (jbertoen) - https://www.drupal.org/u/jbertoen
 
Special thanks to Lendude, Mirnaxvb and michielnugter for reviewing the module.
