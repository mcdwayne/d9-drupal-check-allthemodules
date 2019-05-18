<?php

namespace Drupal\search_365\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\search_365\Form\SearchForm;
use Drupal\search_365\Search365Exception;
use Drupal\search_365\SearchClientInterface;
use Drupal\search_365\SearchResults\SearchQuery;
use Drupal\search_365\SearchResults\SortByLinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchView.
 *
 * @package Drupal\search_365\Controller
 */
class SearchViewController extends ControllerBase {

  /**
   * Search service.
   *
   * @var \Drupal\search_365\SearchClientInterface
   */
  protected $search;

  /**
   * The search 365 settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new SearchViewController object.
   *
   * @param \Drupal\search_365\SearchClientInterface $search
   *   Search service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(SearchClientInterface $search, FormBuilderInterface $formBuilder, ConfigFactoryInterface $configFactory) {
    $this->formBuilder = $formBuilder;
    $this->search = $search;
    $this->config = $configFactory->get('search_365.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_365.search'),
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * Builds search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param string $search_query
   *   Search query.
   *
   * @return array
   *   Render array
   */
  public function get(Request $request, $search_query = '') {
    $search_query = urldecode($search_query);
    $title = $this->config->get('display_settings.search_title');
    $form = $this->formBuilder->getForm(SearchForm::class, $search_query);
    if ($search_query !== '' && !$request->request->has('form_id')) {
      $page = 1;
      if ($request->query->has('page')) {
        // Search 365 pager starts at 1.
        $page = $request->query->get('page') + 1;
      }
      $sortBy = $request->query->get('sortby');
      $pageSize = $this->config->get('display_settings.page_size');
      $query = new SearchQuery($search_query, $page, $pageSize, $sortBy);

      try {
        $resultSet = $this->search->search($query);
      }
      catch (Search365Exception $e) {
        $this->messenger()->addError($this->t('Error communicating with search service. Please try again later.'));
        watchdog_exception('search_365', $e);
        return [];
      }

      pager_default_initialize($resultSet->getResultsCount(), $pageSize);

      $sortLinks = SortByLinkGenerator::getSortByLinks($query);
      $basePath = $this->config->get('display_settings.drupal_path');

      return [
        '#theme' => 'search_365_search_results',
        '#title' => $title,
        '#search_query' => $search_query,
        '#results' => $resultSet,
        '#pager' => ['#type' => 'pager'],
        '#sort_links' => $sortLinks,
        '#base_path' => $basePath,
        '#form' => $form,
        '#cache' => [
          'tags' => ['config:search_365.settings'],
        ],
      ];
    }
    return $form;
  }

}
