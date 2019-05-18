<?php

namespace Drupal\flexible_google_cse;

/**
 * Class FlexibleSearch.
 */
class FlexibleSearch implements FlexibleSearchInterface {

  protected $searchConfig;

  /**
   * Constructs a new FlexibleSearch object.
   */
  public function __construct($config) {
    $config = \Drupal::config('flexible_google_cse.settings');
    $this->searchConfig = $config;
  }

  /**
   * Render search results.
   */
  public function search() {
    $resultLayout = $this->searchConfig->get('result_layout') ? $this->searchConfig->get('result_layout') : 'gcse:searchresults-only';

    $attributes = [
      'queryParameterName' => "key-word",
      'linktarget' => '_parent',
    ];

    if ($this->searchConfig->get('result_size')) {
      $attributes['resultSetSize'] = $this->searchConfig->get('result_size');
    }

    if ($this->searchConfig->get('result_empty_text')) {
      $attributes['noResultsString'] = $this->searchConfig->get('result_empty_text');
    }

    return [
      '#type' => 'html_tag',
      '#tag' => $resultLayout,
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['flexible_google_cse/flexible_google_search'],
        'drupalSettings' => [
          'flexible_google_cse' => [
            'gse_key' => $this->searchConfig->get('gse_key'),
          ],
        ],
      ],
    ];
  }

}
