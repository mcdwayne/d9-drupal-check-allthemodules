<?php

/**
 * @file
 * Theme functions for the uc_ups module.
 */

/**
 * Theming of the customer-facing USPS service name and rate amount line-item.
 *
 * @param array $variables
 *   Associative array containing information needed to theme a quote.
 *   Contains two keys:
 *   - service: The UPS service name.
 *   - packages: Package information.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_ups_option_label(array $variables) {
  $service = $variables['service'];
  $packages = $variables['packages'];

  // Start with logo as required by the UPS terms of service.
  $build['image'] = [
    '#theme' => 'image',
    '#uri' => drupal_get_path('module', 'uc_ups') . '/images/uc_ups_logo.jpg',
    '#alt' => t('UPS logo'),
    '#attributes' => ['class' => ['ups-logo']],
  ];
  // Add the UPS service name.
  $build['label'] = [
    '#plain_text' => t('@service Rate', ['@service' => $service]),
  ];
  // Add package information.
  $build['packages'] = [
    '#plain_text' => ' (' . \Drupal::translation()->formatPlural(count($packages), '1 package', '@count packages') . ')',
  ];

  return drupal_render($build);
}
