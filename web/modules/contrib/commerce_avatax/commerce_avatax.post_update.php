<?php

/**
 * @file
 * Post update functions for Commerce Avatax.
 */

use Drupal\commerce_tax\Entity\TaxType;

/**
 * Update old config data to tax type config.
 */
function commerce_avatax_post_update_1() {
  // Load old config.
  $config = \Drupal::service('config.factory')->getEditable('commerce_avatax.settings');

  if ($config) {
    TaxType::create([
      'id' => 'avatax',
      'label' => 'Avatax',
      'plugin' => 'avatax',
      'configuration' => [
        'display_inclusive' => FALSE,
        'account_id' => $config->get('account_number'),
        'license_key' => $config->get('license_key'),
        'company_code' => $config->get('company_code'),
        'api_mode' => $config->get('api_mode'),
        'tax_code' => $config->get('tax_code'),
      ],
    ]
    )->save();

    // Delete old config.
    $config->delete();
  }
}

/**
 * Migrate back to using Configuration for managing Avatax settings.
 */
function commerce_avatax_post_update_2() {
  $entity_type_manager = Drupal::entityTypeManager();
  // Pick the first available Avatax tax plugin.
  $tax_types = $entity_type_manager->getStorage('commerce_tax_type')->loadByProperties(['plugin' => 'avatax']);
  if (!$tax_types) {
    return;
  }
  $tax_type = reset($tax_types);
  $plugin_config = $tax_type->getPluginConfiguration();
  $config = \Drupal::service('config.factory')->getEditable('commerce_avatax.settings');
  $keys_to_migrate = [
    'account_id',
    'api_mode',
    'company_code',
    'disable_commit',
    'license_key',
    'shipping_tax_code',
  ];
  foreach ($keys_to_migrate as $key) {
    if (!isset($plugin_config[$key])) {
      continue;
    }
    $config->set($key, $plugin_config[$key]);
  }
  $config->save();
}
