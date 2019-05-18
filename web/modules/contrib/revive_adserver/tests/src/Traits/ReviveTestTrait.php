<?php

namespace Drupal\Tests\revive_adserver\Traits;

/**
 * Provides common helper for the revive adserver test classes.
 */
trait ReviveTestTrait {

  /**
   * Configures the module with a test endpoint.
   */
  public function configureModule() {
    // Setup initial revive configuration.
    $this->drupalGet('admin/config/services/revive-adserver');
    $edit = [
      'delivery_url' => 'ads.myserver.local/delivery',
      'delivery_url_ssl' => 'ads.myserver.local/delivery',
      'publisher_id' => 1,
    ];
    $this->submitForm($edit, 'Save');
  }

  /**
   * Setup the revive adserver zones.
   */
  public function setupAdZones() {
    // Zones can be only imported, not specified in the form. So set them
    // manually for testing purposes.
    $config = \Drupal::configFactory()->getEditable('revive_adserver.settings');
    $config->set('zones', [
      [
        'id' => 1,
        'name' => 'Skyscraper',
        'width' => 120,
        'height' => 600,
      ],
      [
        'id' => 2,
        'name' => 'Classic Banner',
        'width' => 468,
        'height' => 60,
      ],
    ]);
    $config->save();
  }

}
