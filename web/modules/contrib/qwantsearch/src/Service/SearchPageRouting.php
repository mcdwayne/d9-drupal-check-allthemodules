<?php

namespace Drupal\qwantsearch\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class SearchPageRouting {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SearchPageRouting constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $routes['qwantsearch.search_page'] = new Route(
      '/' . trim($this->configFactory->get('qwantsearch.settings')->get('qwantsearch_search_page'), '/ '),
      [
        '_controller' => '\Drupal\qwantsearch\Controller\QwantSearchController::getSearchPageContent',
        '_title_callback' => '\Drupal\qwantsearch\Controller\QwantSearchController::getSearchPageTitle',
      ],
      [
        '_permission' => 'access_qwant_search_page',
      ]
    );
    return $routes;
  }

}
