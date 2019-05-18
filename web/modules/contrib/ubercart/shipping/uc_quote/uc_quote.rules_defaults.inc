<?php

/**
 * @file
 * Rules configurations for shipping quotes modules.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function uc_quote_default_rules_configuration() {
  $configs = [];
  $methods = \Drupal::moduleHandler()->invokeAll('uc_shipping_method');

  foreach ($methods as $method) {
    $set = rules_and([
      'order' => ['type' => 'uc_order', 'label' => t('Order')],
    ]);
    $set->label = t('@method conditions', ['@method' => $method['title']]);

    $configs['get_quote_from_' . $method['id']] = $set;
  }

  return $configs;
}
