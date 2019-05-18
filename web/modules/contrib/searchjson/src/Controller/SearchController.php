<?php

namespace Drupal\search_json\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Search json search controller.
 */
class SearchController extends ControllerBase {

  /**
   * Search json search controller.
   */
  public function searchJsonSearch() {
    return [
      '#theme' => 'searchjson',
      '#Json_var' => $this->t('Json Value'),
      '#attached' => [
        'library' => [
          'search_json/search-styles',
        ],
      ],
    ];
  }

}
