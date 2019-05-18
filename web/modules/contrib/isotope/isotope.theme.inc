<?php

/**
 * @file
 * Preprocessors of isotope module.
 *
 * @TODO: Instead of using item_list, write a loop in the twig. More flexible
 *   for themers.
 */

use Drupal\isotope\Controller\IsotopeController;
use Drupal\isotope\Entity\Presentation;

/**
 * Prepares variables for isotope grid template.
 *
 * @param array $variables
 *   An associative array of variables.
 */
function template_preprocess_isotope_grid(&$variables) {
  $presentation = Presentation::load($variables['presentation']);

  // Assemble list of libraries to attach.
  $plugins = [$presentation->layout_mode];
  $plugins = array_merge($plugins, array_values($presentation->plugins));
  $libraries = array_values(IsotopeController::libraryMapping($plugins));
  $libraries[] = 'isotope/isotope';

  // Add the sorting options to the initial presentation.
  $additional_settings = ['getSortData' => []];
  foreach ($variables['items'] as $item) {
    $item['data'] = !empty($item['data']) ? $item['data'] : [];
    foreach ($item['data'] as $key => $value) {
      $additional_settings['getSortData'][$key] = '.' . $key;
    }
  }

  // Retrieve the desired configuration (plus sorting options).
  $presentation_json = $presentation->toJSON($additional_settings);

  // Make sure the instance name is unique per page load.
  $instance_name = 0;
  $global_instances = &drupal_static(__FUNCTION__);
  $global_instances = isset($global_instances) ? $global_instances : [];
  if (!empty($variables['instance']) && !in_array($variables['instance'], $global_instances)) {
    $instance_name = $variables['instance'];
  }
  else {
    for ($i = 0; $i >= 0; $i++) {
      if (!in_array($i, $global_instances)) {
        $instance_name = $i;
        // Break the infinite loop when successful.
        break;
      }
    }
  }
  $global_instances[] = $instance_name;
  $instance = 'isotope-instance-' . $instance_name;

  $items = [
    [
      '#markup' => '',
      '#wrapper_attributes' => ['class' => ['js-isotope-grid-sizer']],
    ],
    [
      '#markup' => '',
      '#wrapper_attributes' => ['class' => ['js-isotope-gutter-sizer']],
    ],
  ];

  foreach ($variables['items'] as $item) {
    $sorts = '';
    $item['data'] = !empty($item['data']) ? $item['data'] : [];
    foreach ($item['data'] as $key => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      foreach ($value as $sort) {
        $sorts .= '<div class="js-sort-data ' . $key . '">' . IsotopeController::sanitize($sort) . '</div>';
      }
      // Sanitize value for the class name below.
      $item['data'][$key] = IsotopeController::sanitize($value);
    }
    $classes = array_values($item['data']);
    $classes[] = 'js-isotope-element';
    $items[] = [
      '#markup' => $item['value'] . $sorts,
      '#wrapper_attributes' => [
        'class' => $classes,
      ],
    ];
  }

  $variables['grid'] = [
    '#theme' => 'item_list',
    '#items' => $items,
    '#type' => 'ul',
    '#attributes' => [
      'class' => 'js-isotope-grid',
      'id' => $instance,
      'data-isotope' => $presentation_json,
    ],
    '#attached' => [
      'library' => $libraries,
    ],
  ];
}

/**
 * Prepares variables for isotope filter template.
 *
 * @param array $variables
 *   An associative array of variables.
 */
function template_preprocess_isotope_filter(&$variables) {
  $multi_field_logic = 'OR';

  // Assemble attributes.
  $attributes['class'] = 'js-isotope-options';
  if (!empty($variables['instance'])) {
    $attributes['data-instance-id'] = 'isotope-instance-' . $variables['instance'];
  }
  if (!empty($variables['filter_name'])) {
    $attributes['data-filter-group'] = $variables['filter_name'];
  }
  else {
    $attributes['data-filter-group'] = 'unnamed_filter';
  }

  // Assemble items.
  $items[] = [
    '#theme' => 'isotope_filter_button',
    '#value' => '',
    '#label' => !empty($variables['default_label']) ? $variables['default_label'] : t('All'),
  ];
  foreach ($variables['items'] as $key => $label) {
    $keys = explode(',', $key);
    foreach ($keys as $k => $v) {
      $keys[$k] = '.' . IsotopeController::sanitize($v);
    }
    if ($multi_field_logic == 'OR') {
      $keys = implode(', ', $keys);
    }
    else {
      $keys = implode('', $keys);
    }
    $items[] = [
      '#theme' => 'isotope_filter_button',
      '#value' => $keys,
      '#label' => $label,
    ];
  }

  $variables['filter'] = [
    '#theme' => 'item_list',
    '#items' => $items,
    '#type' => 'ul',
    '#title' => !empty($variables['filter_title']) ? $variables['filter_title'] : NULL,
    '#attributes' => $attributes,
  ];
}

/**
 * Prepares variables for isotope sorter template.
 *
 * @param array $variables
 *   An associative array of variables.
 */
function template_preprocess_isotope_sorter(&$variables) {
  $attributes['class'] = 'js-isotope-options js-sorts clearfix';
  if (!empty($variables['instance'])) {
    $attributes['data-instance-id'] = 'isotope-instance-' . $variables['instance'];
  }

  if (!empty($variables['default_label'])) {
    $variables['sorts'] = [$variables['default_label']->__toString() => 'original-order'] + $variables['sorts'];
  }

  $items = [];
  foreach ($variables['sorts'] as $key => $value) {
    $sort = is_array($value) ? implode(',', $value) : $value;
    $label = empty($key) || is_numeric($key) ? $sort : $key;
    $items[] = [
      '#theme' => 'isotope_sorter_button',
      '#value' => $sort,
      '#label' => $label,
    ];
  }

  $variables['sorter'] = [
    '#theme' => 'item_list',
    '#items' => $items,
    '#type' => 'ul',
    '#title' => t('Sort By'),
    '#attributes' => $attributes,
  ];
}
