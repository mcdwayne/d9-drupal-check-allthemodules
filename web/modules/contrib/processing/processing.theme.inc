<?php

/**
 * @file
 * processing.theme.inc
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for processing.js template.
 *
 * Default template: processing-display.html.twig.
 *
 * @param array &$variables
 *   An associative array containing:
 *   - rendermode: The render mode configuration for the text format.
 *   - unique: An unique id to set on the script element.
 *   - code: The processing JavaScript code.
 *   - attributes: Attributes to apply to the canvas element.
 */
function template_preprocess_processing_display(&$variables) {
  $variables['button_text'] = $variables['rendermode'] === 'source' ? 'Render Sketch' : 'View Source';
  $button_class = ['processing__button'];
  $source_class = ['processing__source'];

  if ($variables['rendermode'] === 'only') {
    $button_class[] = 'processing__button--hidden';
    $source_class[] = 'processing__source--hidden';
  }

  $variables['button_attributes'] = new Attribute([
    'class' => $button_class,
  ]);

  $variables['source_attributes'] = new Attribute([
    'class' => $source_class,
  ]);

  $variables['script_attributes'] = new Attribute([
    'class' => ['element-invisible'],
    'type' => 'application/processing',
    'target' => $variables['unique'],
  ]);
}
