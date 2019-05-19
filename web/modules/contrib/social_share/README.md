# Social share links

## Dependencies:

- Typed data module: https://www.drupal.org/project/typed_data


## Overview:

The modules provides social share link plugins. The plugins are configurable
via a field type & field formatter which allows editors to select wanted social
media links.

Each plugin defines its own context parameters, which are used when rendering
the social share link, e.g. the to be shared text and URL.
 

### Default plugins

The default plugins coming with the module focus on the following:
 
 * Easy customizability, thus every plugin has a template so the markup can be
   easily controlled. There are no nice icons out of the box as it is expected
   for the theme to provide this.
   
 * Simple: Use simple sharing URLs and avoid extra javascript if necessary. The
   module comes with a quite simple javascript that takes care of opening the
   sharing URLs in a suiting modal window.

 * Privacy enabled: They do not load external javascript assets.
 
 * Flexible: As every plugin can have its own context parameters, all parameters
   supported by a social share link provider can be configured.
 
### Usage:
 
 * Configure the social share link field type on an entity and be sure to select
   the social share link formatter.
 * In the formatter settings, configure all needed context parameters. You may
   use token replacements for all entity fields and their properties.
   
   Some example token replacements, from a field on a media entity:
   - `{{ media.field_description.processed|striptags }}`
   - `{{ media.field_image.entity|entity_url }}`
   - `{{ media.field_image.entity|entity_url }}`
   
   Some examples from a block with node context, or a field on a node:
   - `{{ node.title.value }}`
   - `{{ node.field_teaser_media.entity.field_image.entity|entity_url }}`

### Todos

 - Get typed data token replacements documented and point to the documentation.
 - Add some UI for picking token replacements once this is provided by the Typed Data module.

## Development

* Social share links are plugins which can be added by modules.
* Context parameters are by default shared with other plugins if the name
  matches, thus use reasonable names and check other plugins for fitting 
  context parameters that can be used as well.
  If parameters are for a certain plugin only, prefix them with your plugin
  name, e.g. "facebook_".
* Plugin IDs should be prefixed with the module name.


## Credits

- Development of the Drupal 8 version: 
  Wolfgang Ziegler // fago
  drunomics GmbH, hello@drunomics.com
  
