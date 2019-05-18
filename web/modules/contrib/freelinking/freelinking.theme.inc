<?php

/**
 * @file
 * Freelinking template preprocess functions.
 */

use Drupal\Core\Template\Attribute;

/**
 * Default implementation for template_preprocess_freelink().
 *
 * @param array &$variables
 *   The render element properties containing:
 *   -
 */
function template_preprocess_freelink(&$variables) {
  if (!isset($variables['attributes'])) {
    $variables['attributes'] = [];
  }
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Default implementation for template_preprocess_freelink_error().
 *
 * @param array &$variables
 *   The render element properties containing:
 *   -
 */
function template_preprocess_freelinking_error(&$variables) {
  $variables['attributes']['classes'] += ['freelink-error', 'freelink-error--' . $variables['plugin']];

  $variables['attributes'] = new Attribute($variables['attributes']);
}
