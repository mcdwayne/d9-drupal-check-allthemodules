<?php

namespace Drupal\lunr\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\lunr\LunrSearchInterface;

/**
 * Delivers responses for Lunr search pages.
 */
class LunrSearchController extends ControllerBase {

  /**
   * Title callback for the search form.
   *
   * @param \Drupal\lunr\LunrSearchInterface $lunr_search
   *   The Lunr search entity.
   *
   * @return array
   *   The render array.
   */
  public function page(LunrSearchInterface $lunr_search) {
    $build = [];

    $build['form'] = [
      '#type' => 'form',
      '#id' => Html::getUniqueId('lunr-search-form'),
      '#method' => 'GET',
      '#attributes' => [
        'class' => [
          'lunr-search-page-form',
          'js-lunr-search-page-form',
        ],
        'data-lunr-search' => $lunr_search->id(),
      ],
    ];

    $id = Html::getUniqueId('search');
    $build['form']['input'] = [
      '#type' => 'search',
      '#title' => $this->t('Keywords'),
      '#id' => $id,
      '#name' => 'search',
      '#attributes' => [
        'class' => [
          'js-lunr-search-input',
        ],
      ],
    ];

    $build['form']['submit'] = [
      '#type' => 'submit',
      '#name' => '',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => [
          'js-lunr-search-submit',
        ],
      ],
      '#weight' => 1,
    ];

    $build['results'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'js-lunr-search-results',
          'lunr-search-results',
        ],
      ],
    ];

    $build['#attached']['library'][] = 'lunr/search';
    $build['#attached']['drupalSettings']['lunr']['searchSettings'][$lunr_search->id()] = [
      'indexPath' => file_url_transform_relative(file_create_url($lunr_search->getIndexPath()) . '?v=' . $lunr_search->getLastIndexTime()),
      'documentPathPattern' => file_url_transform_relative(file_create_url($lunr_search->getDocumentPathPattern()) . '?v=' . $lunr_search->getLastIndexTime()),
      'displayField' => $lunr_search->getDisplayField(),
      'resultsPerPage' => $lunr_search->getResultsPerPage(),
      'id' => $lunr_search->id(),
    ];
    $build['#attached']['drupalSettings']['lunr']['workerPath'] = base_path() . drupal_get_path('module', 'lunr') . '/js/search.worker.js';
    $build['#attached']['drupalSettings']['lunr']['lunrPath'] = base_path() . drupal_get_path('module', 'lunr') . '/js/vendor/lunr/lunr.min.js';

    CacheableMetadata::createFromObject($lunr_search)->applyTo($build);

    $this->moduleHandler()->alter('lunr_search_page', $build, $lunr_search);

    return $build;
  }

  /**
   * Title callback for the search form.
   *
   * @param \Drupal\lunr\LunrSearchInterface $lunr_search
   *   The Lunr search entity.
   *
   * @return string
   *   The title for the page.
   */
  public function title(LunrSearchInterface $lunr_search) {
    return $lunr_search->label();
  }

  /**
   * Determines access for the search page.
   *
   * @param \Drupal\lunr\LunrSearchInterface $lunr_search
   *   The Lunr search entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(LunrSearchInterface $lunr_search) {
    return AccessResult::allowedIf(file_exists($lunr_search->getIndexPath()));
  }

}
