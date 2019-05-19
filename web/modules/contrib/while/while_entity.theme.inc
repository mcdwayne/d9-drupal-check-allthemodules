<?php

/**
 * @file
 * Contains while_entity.page.inc.
 *
 * Page callback for while entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;

/**
 * Prepares variables for While entity templates.
 *
 * Default template: while-entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_while_entity(array &$variables) {
  // Fetch WhileEntity Entity Object.
  $while_entity = $variables['elements']['#while_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['attributes']['class'][] = 'while-entity';
  $variables['attributes']['class'][] = Html::getClass('while-type-' . $while_entity->bundle());

  if ($variables['elements']['#view_mode']) {
    $variables['attributes']['class'][] = Html::getClass('view-mode-' . $variables['elements']['#view_mode']);
  }
}
