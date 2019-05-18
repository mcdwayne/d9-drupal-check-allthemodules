<?php

namespace Drupal\store_locator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Store Locator.
 */
class StoreLocatorPage extends ControllerBase {

  /**
   * Render a list and Map.
   */
  public function page() {
    $content = [];
    $content['searchitem'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'id' => ['search-location'],
        'placeholder' => $this->t('Search keyword'),
      ],
    ];
    // Preprocesses the Results.
    return [
      '#theme' => 'location_data',
      '#location_search' => $content,
    ];
  }

}
