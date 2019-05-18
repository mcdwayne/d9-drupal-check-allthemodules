<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;

/**
 * Prepares variables for view templates.
 *
 * Default template: mixitup-views-view-mixitup.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 *   - options: An array of options. Each option contains:
 *     - separator: A string to be placed between inline fields to keep them
 *       visually distinct.
 */
function template_preprocess_mixitup_views_view_mixitup(array &$variables) {
  $mixitup_func = \Drupal::service('mixitup_views.func_service');
  // Run preprocess function for unformatted style.
  template_preprocess_views_view_unformatted($variables);
  if (!$mixitup_func->isMixitupInstalled()) {
    drupal_set_message(t('mixitup.min.js has not been found in libraries/mixitup/dist directory.'), 'error');
  }
  else {
    $view = $variables['view'];
    $results = $view->result;
    $style = $view->style_plugin;
    $options = $style->options;
    // Pulls sorts data.
    $sort_keys = [];
    if (isset($options['use_sort']) && $options['use_sort'] == 1 && isset($options['sorts'])) {
      foreach ($options['sorts'] as $sort_item => $label) {
        if (empty($label)) {
          unset($options['sorts'][$sort_item]);
          continue;
        }
        $sort_keys[$sort_item] = $label;
      }
    }
    $mixitup_func_service = \Drupal::service('mixitup_views.func_service');
    foreach ($results as $id => $result) {
      if (property_exists($result, 'nid')) {
        $classes = $mixitup_func_service->getRowClasses($result->nid);
      }
      elseif (property_exists($result, 'tid')) {
        $classes = $mixitup_func_service->getRowClasses($result->tid);
      }
      else {
        $classes = '';
      }
      $variables['rows'][$id]['attributes']->addClass('mix_item mix');
      $variables['rows'][$id]['attributes']->addClass($classes);
      // Add sort attributes.
      $sorts_fields = array_intersect_key((array) $result, $sort_keys);
      if (!empty($sorts_fields)) {
        foreach ($sorts_fields as $field_id => $val) {
          $variables['rows'][$id]['attributes']->setAttribute('data-' . $field_id, $val);
        }
      }
    }

    $filters = \Drupal::formBuilder()->getForm('Drupal\mixitup_views\Form\MixitupFiltersForm', $options);
    $variables['filters'] = \Drupal::service('renderer')->render($filters);

    // Display content in a Mixitup layout.
    $container = '.view-' . Html::cleanCssIdentifier($view->storage->get('id')) . '.view-display-id-' . $view->current_display . ' .view-content';
    $settings = mixitup_views_get_settings($container, $options);
    $variables['#attached']['drupalSettings'] = $settings;
    $variables['#attached']['library'][] = 'mixitup_views/mixitup-views';
  }
}

/**
 * Apply mixitup library to container.
 *
 * @param string $container
 *   Container name.
 * @param array $options
 *   Array of mixitup options.
 *
 * @return array
 *   Returns MixitUp settings array.
 */
function mixitup_views_get_settings($container, array $options = []) {
  $mixitup_func = \Drupal::service('mixitup_views.func_service');
  if (!empty($container)) {
    // For any options not specified, use default options.
    $options += $mixitup_func->getDefaultOptions(TRUE);
    // Set of mixitup settings.
    $mixitup_settings = [
      'mixitup_settings_array' => [
        'selector_name' => $container,
        $container => [
          'selectors' => [
            'target' => $options['selectors_target'],
            'control' => '[data-mixitup-control]',
          ],
          'load' => [
            'filter' => $options['load_filter'],
            'sort' => $options['load_sort'],
          ],
          'animation' => [
            'enable' => (bool) $options['animation_enable'],
            'effects' => $options['animation_effects'],
            'duration' => (int) $options['animation_duration'],
            'easing' => $options['animation_easing'],
            'perspectiveDistance' => $options['animation_perspectiveDistance'],
            'perspectiveOrigin' => $options['animation_perspectiveOrigin'],
            'queue' => (bool) $options['animation_queue'],
            'queueLimit' => (int) $options['animation_queueLimit'],
          ],
        ],
      ],
      'filters_form_id' => '#mixitup-views-filters-form',
      'reset_id' => '#reset',
      'filtering_type' => isset($options['filter_type']) ? $options['filter_type'] : 'checkboxes',
      'agregation_type' => isset($options['agregation_type']) ? $options['agregation_type'] : 'and',
    ];

    return $mixitup_settings;
  }
}
