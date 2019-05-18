<?php

/**
 * @file
 * Contains assembly.page.inc.
 *
 * Page callback for Assembly entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for Assembly templates.
 *
 * Default template: assembly.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_assembly(array &$variables) {
  // Fetch Assembly Entity Object.
  $assembly = $variables['elements']['#assembly'];
  $variables['assembly'] = $assembly;

  $type = $assembly->bundle();
  $variables['attributes'] = new Attribute($variables['attributes']);

  // Visual styles
  foreach ($assembly->get('visual_styles')->getValue() as $style) {
    $variables['attributes']->addClass(Html::cleanCssIdentifier($style['value']));
  }

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Add id and class with assembly type
  $variables['attributes']->offsetSet('id', 'assembly-id-' . $assembly->id());
  $variables['attributes']->addClass(['assembly', 'assembly-type-' . $type]);
}
