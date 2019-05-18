<?php

namespace Drupal\funnelback\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The FunnelbackPageController controller.
 */
class FunnelbackPageController extends ControllerBase {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Page callback for funnelback search.
   */
  public function getSearchView() {

    // Create an array of query params.
    $request_params = drupal_get_query_parameters();

    $funnelback = funnelback_get_funnelback();
    $funnelbackRequest = new FunnelbackClient();

    if (!isset($request_params['query'])) {
      return [
        '#theme' => 'funnelback_results',
        '#items' => [],
        '#total' => 0,
        '#query' => NULL,
        '#curator' => [],
        '#pager' => [],
        '#summary' => NULL,
        '#breadcrumb' => [],
        '#spell' => [],
        '#no_result_text' => NULL,
        '#attached' => [
          'css' => [
            [
              'data' => drupal_get_path('module', 'funnelback') . '/css/funnelback.css',
              'type' => 'file',
            ],
          ],
        ],
      ];
    }
    else {
      $query = filter_xss($request_params['query']);
    }
    $start = (!empty($request_params['start_rank'])) ? filter_xss($request_params['start_rank']) : 0;

    $raw_queries = explode('&', $_SERVER['QUERY_STRING']);

    // Filter the facet params out.
    $facet_query = FunnelbackQueryString::funnelbackFilterFacetQueryString($raw_queries);

    // Filter the contextual params out.
    $contextual_query = FunnelbackQueryString::funnelbackFilterContextualQueryString($raw_queries);

    $results = $funnelback->funnelbackDoQuery($query, $start, NULL, $facet_query, $contextual_query, $funnelbackRequest, funnelback_get_config()->get('general_settings.custom_template'));

    $output = [];

    // Check custom template usage.
    if (!empty($results) && Funnelback::funnelbackResultValidator($results)) {
      // Composer array of rendered results.
      $items = [];
      foreach ($results['results'] as $result) {
        // Use funnelback summary by default.
        $render_item = [
          '#theme' => 'funnelback_result',
          '#display_url' => $result['display_url'],
          '#live_url' => $result['live_url'],
          '#title' => $result['title'],
          '#date' => $result['date'],
          '#summary' => $result['summary'],
          // Pass any custom metadata to theme for further customisation.
          '#metadata' => $result['metaData'],
        ];
        $items[] = $render_item;

        // Use view mode if local node.
        if ($result['metaData']['nodeId'] && funnelback_get_config()->get('display_mode.enabled')) {
          // Check if the node is local.
          $live_url = $result['live_url'];
          global $base_url;
          if (parse_url($base_url, PHP_URL_HOST) == parse_url('http://' . $live_url, PHP_URL_HOST)) {
            // NodeId and view mode are available. Use view mode to render node.
            $view_mode = funnelback_get_config()->get('display_mode.id');
            $node = $this->entityManager->getStorage('node')->load($result['metaData']['nodeId']);
            // Check node still exist. Do not display result if node exist in
            // Funnelback index but removed in local database.
            if ($node) {
              $items[] = $this->entityManager
                ->getViewBuilder('node')
                ->view($node, $view_mode);
            }
          }
        }
      }

      $summary = [
        '#theme' => 'funnelback_summary',
        '#summary' => $results['summary'],
      ];

      // Detect if there is any filter selected.
      $selected = FALSE;
      foreach ($results['facets'] as $facet) {
        if ($facet['selected'] == TRUE) {
          $selected = TRUE;
        }
      }
      Funnelback::funnelbackFilterFacetDisplay($results['facets']);
      $breadcrumb = [
        '#theme' => 'funnelback_breadcrumb',
        '#facets' => $results['facets'],
        '#facet_extras' => $results['facetExtras'],
        '#selected' => $selected,
      ];
      $spell = [
        '#theme' => 'funnelback_spell',
        '#spell' => $results['spell'],
      ];
      $curator = [
        '#theme' => 'funnelback_curator',
        '#curator' => $results['curator'],
      ];
      $pager = [
        '#theme' => 'funnelback_pager',
        '#summary' => $results['summary'],
      ];

      // Sanitise no-result-text with query token.
      $no_result_text = funnelback_get_config()->get('general_settings.no_result_text', NULL);
      $no_result_text = filter_xss_admin(str_replace('[funnelback-query]', $query, $no_result_text));

      $output = [
        '#theme' => 'funnelback_results',
        '#items' => $items,
        '#total' => $results['summary']['total'],
        '#query' => $results['summary']['query'],
        '#curator' => $curator,
        '#pager' => $pager,
        '#summary' => $summary,
        '#breadcrumb' => $breadcrumb,
        '#spell' => $spell,
        '#no_result_text' => $no_result_text,
        '#attached' => [
          'css' => [
            [
              'data' => drupal_get_path('module', 'funnelback') . '/css/funnelback.css',
              'type' => 'file',
            ],
          ],
        ],
      ];
    }
    else {
      drupal_set_message($this->t('There was an error connecting to funnelback, please enable debug and check the log.'), 'warning');
    }

    return $output;
  }

  /**
   * Page callback for autocompletion request.
   */
  public function getAutocompletionResult($partial_query) {

    $funnelback = funnelback_get_funnelback();
    $funnelbackRequest = new FunnelbackClient();

    $results = $funnelback->funnelbackDoQuery(NULL, NULL, $partial_query, NULL, NULL, $funnelbackRequest);

    // Add key to suggest array.
    $suggests = [];
    foreach ($results as $result) {
      $suggests[$result['key']] = $result['key'];
    }

    return new JsonResponse($suggests);
  }

}
