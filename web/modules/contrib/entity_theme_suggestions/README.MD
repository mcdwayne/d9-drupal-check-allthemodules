## Entity Theme Suggestions
This module provides a plugin which can define new theme suggestions for 
entities. It replaces hook_theme_suggetions_alter(). The module provides a base 
suggestions class, "EntityThemeSuggestionsBase", that can be extended by any 
plugin so that it inherits the most common theme suggestions. 

## Usage
Create a class in "Plugin/ThemeSuggestions" directory structure of your module, 
that implements "EntityThemeSuggestionsInterface" interface. The 
"alterSuggestions" function should return all the theme suggestions for your 
entity. 

To mark your class as an entity theme suggestions plugin, you have to add an 
annotation: 
```php
/**
 * @EntityThemeSuggestions(
 *   id = "your_plugin_id",
 *   entityType = "your_entity_type_id",
 *   priority = "the priority of the suggestion"
 * )
 */
```

The plugin id is required and has to be unique. The entity type is required 
also. The priority field is optional and set to 1 by default.

## Example:
```php
<?php

namespace Drupal\example\Plugin\ThemeSuggestions;

use Drupal\entity_theme_suggestions\EntityThemeSuggestionsBase;

/**
 * Alters suggestions for user.
 *
 * @EntityThemeSuggestions(
 *   id = "user_theme_suggestions",
 *   entityType = "user"
 * )
 */
class UserThemeSuggestions extends EntityThemeSuggestionsBase {
}
```

This code creates the provided default suggestions from the module for the user
 entity. The suggestions are defined in the base class by the 
"alterSuggestions()" method. The method provides the following suggestions:

  - [entity_type_id]
  - [entity_type_id]--[view_mode]
  - [entity_type_id]--[bundle]
  - [entity_type_id]--[bundle]--[view_mode]
  - [entity_type_id]--[entity_id]
  - [entity_type_id]--[entity_id]--[view_mode];

