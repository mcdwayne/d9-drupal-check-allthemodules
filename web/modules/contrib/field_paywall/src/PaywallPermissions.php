<?php

/**
 * @file
 * Contains \Drupal\field_paywall\PaywallPermissions.
 */

namespace Drupal\field_paywall;


/**
 * Provides dynamic permissions for the Field Paywall module.
 */
class PaywallPermissions {

  /**
   * Get bypass permissions for each paywall field on the site.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];

    $paywall_fields = entity_load_multiple_by_properties('field_config', array(
      'field_type' => 'paywall',
    ));

    foreach ($paywall_fields as $paywall_field) {
      $permission_name = 'bypass ' . $paywall_field->uuid();
      $permissions[$permission_name] = [
        'title' => t('Bypass the %field_name paywall for %bundle_name %entity_name entities.', [
          '%field_name' => $paywall_field->getName(),
          '%bundle_name' => $paywall_field->bundle,
          '%entity_name' => $paywall_field->getTargetEntityTypeId(),
        ]),
        'description' => t('The paywall will not be shown and no fields will be hidden.'),
      ];
    }

    asort($permissions);

    return $permissions;
  }
}