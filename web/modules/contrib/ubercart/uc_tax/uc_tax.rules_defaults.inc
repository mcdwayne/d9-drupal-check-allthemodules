<?php

/**
 * @file
 * Default Rules configurations for uc_taxes.
 */

/**
 * Implements hook_default_rules_configuration().
 *
 * Creates a condition set for each tax rule.
 */
function uc_tax_default_rules_configuration() {
  $configs = [];

  // Loop through all the defined tax rates.
  foreach (uc_tax_rate_load() as $rate) {
    $set = rules_and([
      'order' => ['type' => 'uc_order', 'label' => 'Order'],
    ]);
    $set->label = t('@name conditions', ['@name' => $rate->name]);

    $configs['uc_tax_' . $rate->id] = $set;
  }

  return $configs;
}
