<!-- @file Project Page -->
# Drupal+

> Provides missing object-oriented functionality in Drupal, primarily focused on enhancing the theme system and performance.

### Requirements

- Any Modern Browser (e.g. no official Internet Explorer support)

### Features

- **Annotations**
  - `@Alter` - Create class based hook alters.
  - `@FormAlter` - Create class based form alters.
  - `@Template` - Create class based templates (formerly known as "theme hooks")
  - `@Theme` - Create class based themes.
  - `@ThemeSetting` - Create class based theme settings.
  - `@Update` - Create class based updates.
- **Events**
  - `ThemeEvents` - Events triggered during installation or activation.
    - `ACTIVATE` - Triggered before a theme is set as active.
    - `ACTIVATED` - Triggered after a theme is set as active.
    - `INSTALL` - Triggered before a theme is installed.
    - `INSTALLED` - Triggered after a theme is installed.
    - `UNINSTALL` - Triggered before a theme is uninstalled.
    - `UNINSTALLED` - Triggered after a theme is uninstalled.
- **JavaScript**
  -  Equivalent PHP counterparts:
    - `Html.es6.js` - `\Drupal\Component\Utility\Html`
    - `Attributes.es6.js` - `\Drupal\Core\Template\Attribute`  
      Note: This isn't a 1:1 implementation (see `Attributes` utility below)
- **Render array improvements**
  - `#type`
    - Adds support for theme hook suggestions
- **Traits**
  - `PluginSerializationTrait` - Helper for serializing plugins.
  - `RendererTrait` - Helper for accessing the Renderer service.
  - `SerializationTrait` - Helper for serializing objects.
- **Utilities**
  - `ArrayObject` - extension of SPL `\ArrayObject`
  - `Attributes` - better attributes support that essentially replaces the
    horribly implemented `\Drupal\Core\Template\Attribute` class.
  - `DrupalArray` - Object for manipulating common "Drupal Arrays".
  - `Element` - Object for manipulating render array elements in Drupal.
  - `Variables` - Object for manipulating template variables in Drupal.


### Versions

This module keeps major version parity with core. For example, if you're using
Drupal `8.4.x`, then use releases from the `8.x-4.x` branch. If using Drupal
`8.5.x`, then use releases from the `8.x-5.x` branch and so forth.
