# PREPROCESS

_Thanks for taking the time to check this readme!_

## INTRODUCTION

This module provides a plugin type for preprocessing. 
The manager scans for implementations and executes the preprocessors when they 
meet the given `hook` criteria. It is designed to make preprocessing more 
structural and more clearly for themers.

With this module you no longer have to write all your preprocessing in your 
THEME.theme or module file. You gain the possibility to focus preprocessing on 
specific elements without having to write so many conditional checks for one
specific hook implementation to cover all cases.

## REQUIREMENTS

This module has no hard dependencies, but requires at least PHP 7.1.

## INSTALLATION

Install this module as any other Drupal module, see the documentation on
[Drupal.org](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## CONFIGURATION

This module requires no extra configuration.

## USAGE

The plugin manager scans for implementations and executes the preprocessors 
when they meet the given `hook` criteria.

The definition of preprocessor plugins require two properties:
- Class
    - The, fully name spaced, class that handles the preprocessing.
- Hook
    - The hook that should trigger the preprocessor. 
    This corresponds to `hook_preprocess_HOOK`

Plugins can be registered in two ways:

### Registering preprocessors using class annotations
One of the options to register preprocessors is to use annotations. 
This is the easiest way to register preprocessors if you are using a module.
To do this, you put a *`@Preprocess`* annotation above the preprocess class 
using only the the hook key unlike the definition in *.preprocessors.yml. 

Here's a simple example:

File `/src/Plugin/Preprocess/MyCustomBlock.php`:

<span id="plugin-class-example"></span>
```php
<?php
namespace Drupal\my_custom_module\Plugin\Preprocess;

use Drupal\preprocess\PreprocessPluginBase;

/**
 * My custom block preprocessing.
 *
 * @Preprocess(
 *   id = "my_custom_module.preprocess.block",
 *   hook = "block"
 * )
 */
class MyCustomBlock extends PreprocessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array $variables): array {
    // Do any preprocessing here for your block!
    return $variables;
  }

}
```

### Registering preprocessors using *.preprocessors.yml
The most basic way to register preprocessors, is to put a *.preprocessors.yml 
file in your module or theme. For themes this is the only way to actually 
register a preprocessor, as annotation discovery does not work for themes.

The first part of the file name is the machine name of your module or theme, so 
if for example, your module's machine name is my_custom_module, you'd call the
file my_custom_module.preprocessors.yml.

This file should be placed in the top-level directory of your module or theme, 
and you'll need to rebuild the cache (for example, with Drush it's drush cr) 
for your changes to the file to be picked up.

**Example \*.preprocessors.yml:**
```
my_custom_module.preprocess.block:
  class: \Drupal\my_custom_module\Plugin\Preprocess\Block
  hook: block
```

The class will actually have the same implementation(s) as the example given in
[Registering preprocessors using class annotations](#plugin-class-example). 
The only difference will be that you do not use annotation here.
