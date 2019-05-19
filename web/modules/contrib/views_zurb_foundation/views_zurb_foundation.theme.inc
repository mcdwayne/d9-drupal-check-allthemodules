<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\views_zurb_foundation\ViewsZurbFoundation;

/**
 * Prepares variables for views grid templates.
 *
 * Default template: views-zurb-foundation-block-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_zurb_foundation_block_grid(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsZurbFoundation::getUniqueId($view);
  $vars['attributes']['class'][] = 'grid';
  $options = $view->style_plugin->options;

  $options['block_grid_breakpoints'] = [
    'small' => $options['small'],
    'medium' => $options['medium'],
    'large' => $options['large'],
  ];

  $options['block_grid'] = implode(' ', $options['block_grid_breakpoints']);

  $vars['options'] = $options;
}
