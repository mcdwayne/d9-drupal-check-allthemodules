<?php

/**
 * @file
 * Preprocessors for field_group_easy_responsive_tabs elements.
 */
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for tabs templates.
 *
 * Default template: field-group-easy-responsive-tabs.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 */
function template_preprocess_field_group_easy_responsive_tabs(&$variables) {
  $element = $variables['element'];

  if (isset($element['#id'])) {
    $element['#attributes']['id'] = $element['#id'];
  }

  $variables['attributes'] = new Attribute($element['#attributes']);

  $navigation_attributes = [
    'class' => [
      'resp-tabs-list',
      $element['#id'],
    ],
  ];

  $variables['navigation_attributes'] = new Attribute($navigation_attributes);
  $variables['navigation'] = [];

  foreach (Element::children($element) as $child) {
    if (empty($element[$child]) || empty($element[$child]['#type'])) {
      continue;
    }

    if ($element[$child]['#type'] === 'field_group_easy_responsive_tab') {
      $variables['navigation'][] = $element[$child]['#title'];
    }
  }

  $container_attributes = [
    'class' => [
      'resp-tabs-container',
      $element['#id'],
    ],
  ];

  $variables['container_attributes'] = new Attribute($container_attributes);

  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for tab templates.
 *
 * Default template: field-group-easy-responsive-tab.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 */
function template_preprocess_field_group_easy_responsive_tab(&$variables) {
  $element = $variables['element'];

  if (isset($element['#id'])) {
    $element['#attributes']['id'] = $element['#id'];
  }

  $variables['attributes'] = new Attribute($element['#attributes']);

  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}
