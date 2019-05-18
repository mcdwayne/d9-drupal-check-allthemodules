<?php

/**
 * @file
 * Hooks provided by the micro_theme module.
 */

/**
 * Permit to alter or add additionnal color to the default color parameters.
 *
 * Modules which want to add others color can add them here as the service
 * MicroLibrariesService do.
 * @See MicroLibrariesService->getDefaultColors() method
 *
 * @param array $yaml_default_color
 *   An array of colors keyed by the color key. Example:
 *   text_color:
 *     - value: '#212529' (The default value for the color
 *     - name: 'Text color' (The display name)
 *     - weight: 1
 *
 * Color with a lighter weight will be processed and replaced first. Useful for
 * color key as PRIMARY_COLOR and PRIMARY_COLOR_HOVER for which you want that
 * the color key PRIMARY_COLOR_HOVER si processed and replaced first.
 *
 */
function hook_micro_theme_default_color_alter(array &$yaml_default_color) {

}

/**
 * Permit to alter or add additionnal fonts to the default fonts provided.
 *
 * Modules which want to add others font can add them here as the service
 * MicroLibrariesService do. They can use the public method getModuleLibraries()
 * to list all libraries provide by a module or the method getThemeLibraries()
 * from a theme.
 * @See MicroLibrariesService->getFonts() method
 *
 * @param array $options
 *   An array keyed by the font library key, and with the display name for the
 *   value.
 *
 */
function hook_micro_theme_fonts_alter(array &$options) {

}
