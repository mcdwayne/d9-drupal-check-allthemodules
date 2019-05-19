<?php

/**
 * @file
 * Hooks for the svg_icon_field module.
 */

/**
 * Allows modules to alter categories.
 *
 * Defining a new category is a matter of providing new
 * array of details describing a category. There is couple of
 * keys that needs to be created.
 *
 * @param array &$categories
 *   List of categories.
 */
function hook_svg_icon_field_categories_alter(array &$categories) {
  // We're adding new 'mymodule_cars' icons category.
  $categories['categories']['mymodule_cars'] = [
    // Readable label. This label is processed with t() later,
    // so no need to provide it here.
    'label' => 'Cars',
    // If the icons requries attribution you can place a text here.
    'attribution' => '',
    // Group where your icons are going to be located. You can
    // provide custom one.
    'group' => 'Internet',
    // Three options below describes a detailed path where icons are storred.
    // Out of it the path is going to be rendered, eg.:
    // modules/custom/mymodule/icons/cars.
    // First is type of element (eg. module / theme / profile)
    'element_type' => 'module',
    // This is the name of element (eg. mymodule).
    'element_name' => 'mymodule',
    // This is the path where icons are storred.
    'icons_path' => 'icons/cars',
  ];
}
