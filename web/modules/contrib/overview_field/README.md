# Overview field

## Overview
Provides a custom field with a dropdown list that contains a list of
overview / custom blocks.

## Usage

Add an option to the dropdown list:

```
/**
 * Implements hook_overview_field_options_alter().
 *
 * Add an option to the overview field.
 */
function overview_field_example_overview_field_options_alter(&$options) {
  $options['recent_content'] = t('Show a list of the recent content on the site');
  $options['example_block'] = t('Loads a block');
}
```

Load the output for the previously declared option.

```
/**
 * Implements hook_overview_field_output_alter().
 */
function overview_field_example_overview_field_output_alter($key, &$output) {
  if ($key == 'recent_content') {
    $output = overview_field_load_view('content_recent', 'block_1');
  }
  if ($key == 'example_block') {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance('block_name', $config);
    $output = $plugin_block->build();
  }
}
```

## Roadmap
* Add tests to the module.
* Add extra options with Plugin derivatives instead of alters.
