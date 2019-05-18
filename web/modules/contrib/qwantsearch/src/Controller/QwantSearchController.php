<?php

namespace Drupal\qwantsearch\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\qwantsearch\Service\QwantSearchDisplayInterface;
use Drupal\qwantsearch\Service\QwantSearchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QwantSearchController.
 *
 * @package Drupal\Controller\Controller
 */
class QwantSearchController extends ControllerBase {

  /**
   * Qwant Search Interface.
   *
   * @var \Drupal\qwantsearch\Service\QwantSearchInterface
   */
  protected $qwantSearchService;

  /**
   * Query factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The http request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Qwant Display Service.
   *
   * @var \Drupal\qwantsearch\Service\QwantSearchDisplayInterface
   */
  protected $qwantDisplay;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, QwantSearchInterface $qwantSearch, Request $request, QwantSearchDisplayInterface $qwantDisplay) {
    $this->qwantSearchService = $qwantSearch;
    $this->configFactory = $configFactory;
    $this->request = $request;
    $this->qwantDisplay = $qwantDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('qwantsearch.qwantsearch'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('qwantsearch.display')
    );
  }

  /**
   * Returns search page content (results from qwant).
   *
   * @return array
   *   Returns a renderable array.
   */
  public function getSearchPageContent() {
    $content = [];
    $qwant_params = [
      'q' => $this->request->get('search'),
    ];
    $response = $this->qwantSearchService->makeQuery($qwant_params);
    if (!$this->qwantSearchService->isSuccess($response)) {
      $content['error'] = [
        '#markup' => $this->t('An error occurred. Please check that Qwant services are running and contact an administrator.'),
      ];
      return $content;
    }

    $content = $this->qwantDisplay->prepareRenderableResults($response->data->results);

    return $content;
  }

  /**
   * Returns search page title according to configuration.
   *
   * @return string
   *   Search page title.
   */
  public function getSearchPageTitle() {
    return $this->configFactory->get('qwantsearch.settings')->get('qwantsearch_search_page_title');
  }

}
