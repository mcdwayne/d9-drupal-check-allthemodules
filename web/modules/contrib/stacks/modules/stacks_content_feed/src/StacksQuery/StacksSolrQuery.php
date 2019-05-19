<?php
/**
 * @file
 * Contains \Drupal\stacks_content_feed\Grid\GridSolrQuery
 */

namespace Drupal\stacks_content_feed\StacksQuery;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\stacks_content_feed\StacksQuery\StacksQueryBase;

/**
 * Class GridSolrQuery
 *
 * Code that is shared by all grids goes here.
 */
class StacksSolrQuery extends StacksQueryBase {

  protected $indexName;
  protected $fulltextField;

  public function __construct($unique_id, $search_api_index, $fulltext_field) {
    $this->unique_id = $unique_id;
    $this->indexName = $search_api_index;
    $this->fulltextField = $fulltext_field;
  }

  /**
   * Queries search_api for nods
   *
   * @param $options (array)
   *  status: (1/0)
   *  content_types: Array of content type bundle machine names.
   *  taxonomy: Array of field name and terms to limit by. Each row:
   *    kay - field_name: The name of the taxonomy field.
   *    value - tids: An array of term ids.
   *  total_results: Number of results to return. Don't use this if setting
   *    per_page. Useful for displaying a set number of results. Set 0 for all.
   *  per_page: Number of results per page (ajax).
   *  order_by: Order nodes. See $this->queryNodeSort.
   * @return array : An array of node objects.
   * @throws \Exception
   */
  public function getNodeResults($options) {
    $unique_id = $this->unique_id;
    $index_name = $this->indexName;
    $fulltext_field = $this->fulltextField;

    // If pagination is not set to none, and if per_page is not set, set the
    // per_page value.
    if (!empty($options['pagination_type']) && empty($options['per_page'])) {
      $options['per_page'] = 10;
    }

    $search_api_index = Index::load($index_name);
    if ($search_api_index !== NULL) {

      // Check out if Solr server is available
      $server = Server::load($search_api_index->getServerId());
      if (!$server->isAvailable()) {
        $error_message = t("Stacks: Solr server is not available.");
        drupal_set_message($error_message, 'error');
        \Drupal::logger('stacks')->error("Solr server is not available.");
        return [];
      }

      $current_page = !is_null(\Drupal::request()->query->get('page')) ? \Drupal::request()->query->get('page') : 0;
      $current_page = preg_replace("/[^0-9]/", "", $current_page);

      $page = $current_page * $options['per_page'];

      // Create the query.
      if (empty($options['pagination_type']) && empty($options['per_page'])) {
        $query = $search_api_index->query([
          'search id' => 'search_api_page:content_feed_id_' . $unique_id,
        ]);
      }
      else {
        $query = $search_api_index->query([
          'limit' => $options['per_page'],
          'offset' => $page,
          'search id' => 'search_api_page:content_feed_id_' . $unique_id,
        ]);
      }

      // Search API Solr Version <= alpha15
      //$query->setParseMode('direct');
      // Search API Solr Version > than alpha15
      $parse_mode = \Drupal::getContainer()
        ->get('plugin.manager.search_api.parse_mode')
        ->createInstance('direct');
      $query->setParseMode($parse_mode);

      $query->addCondition('status', isset($options['status']) ? $options['status'] : 1, '=');

      $query->setFulltextFields([
        'title' => 'title',
        'body' => 'body',
        $fulltext_field => $fulltext_field,
      ]);

      if (isset($options['content_types']) && $options['content_types']) {
        $query->addCondition('type', $options['content_types'], 'IN');
      }

      // Taxonomy Terms.
      if (isset($options['taxonomy']) && count($options['taxonomy']) > 0) {
        foreach ($options['taxonomy'] as $field_name => $tids) {
          if ($tids) {
            if ($field_name == 'ui_selected_tags') {
              if (isset($options['taxonomy_terms_field_names']) && is_array($options['taxonomy_terms_field_names']) && count($options['taxonomy_terms_field_names'])) {
                $group = $query->createConditionGroup('OR', ['ui_selected_tags']);
                foreach ($options['taxonomy_terms_field_names'] as $fnames) {
                  foreach ($fnames as $fname) {
                    $group->addCondition($fname, $tids, 'IN');
                  }
                }
                $query->addConditionGroup($group);
              }
              else {
                // Error condition.
                drupal_set_message(t("Selected content types do not have suitable taxonomy term fields. Ignoring taxonomy terms filter."), 'error');
              }
            }
            else {
              $query->addCondition($field_name, $tids, 'IN');
            }
          }
        }
      }

      // Text search.
      if (isset($options['search']) && !empty($options['search'])) {
        $query->keys($options['search']);
      }

      // Make sticky appear at the top?
      if (isset($options['sticky']) && $options['sticky'] == TRUE) {
        $query->sort('sticky', 'DESC');
      }

      if (isset($options['limit_by']) && $options['limit_by'] == 'promote') {
        $query->addCondition('promote', TRUE, '=');
      }

      // Appends parameters invoking hook_widget_node_results_alter()
      $options['search_api_type'] = 'solr';
      foreach (\Drupal::moduleHandler()
                 ->getImplementations('widget_node_results_alter') as $module) {
        \Drupal::moduleHandler()
          ->invoke($module, 'widget_node_results_alter', [
            &$query,
            NULL,
            &$options,
          ]);
      }

      // Handle sorting.
      $this->getNodeResultsSort($query, $options['order_by']);

      $result = $query->execute();

      $items = $result->getResultItems();

      // Limit the number of results of the query.
      if ($options['per_page'] > 0) {
        // Pagination.
        $session = \Drupal::service('session');

        if (!$session->isStarted()) {
          $session->start();
        }

        $pager = $session->get('pager_elements');

        // Get query full count in session variable in order to fix page
        // count when loading page.
        $num_rows = $result->getResultCount();
        $pager[$unique_id] = ceil($num_rows / $options['per_page']);

        // Getting page query to rebuild pager.
        $page = \Drupal::request()->query->get('page', '');

        // Rewriting page parameter to trigger pager refreshing:
        // check out https://api.drupal.org/api/drupal/core%21includes%21pager.inc/function/pager_find_page/8.2.x
        if (!empty($page)) {
          $page_array = [];

          for ($i = 0; $i < $unique_id; $i++) {
            $page_array[] = '';
          }
          $page_array[] = $page;

          \Drupal::request()->query->set('page', implode(',', $page_array));
        }

        pager_default_initialize($num_rows, $options['per_page'], $unique_id);

        $session->set('pager_elements', $pager);
      }

      $nids = [];
      if (is_array($items)) {
        foreach ($items as $item) {
          list(, $path) = explode(':', $item->getId());
          list(, $id) = explode('/', $path);

          $nids[$id] = $id;
        }
      }

      return $nids;

    }
    else {
      $error_message = t("Stacks: Content Feed SOLR error. Please check the reports /admin/reports/dblog");
      drupal_set_message($error_message, 'error');
      \Drupal::logger('stacks')->error(
        "Content Feed solr search: requested index is not found (@index_name) go to /admin/config/search/search-api and create one",
        ['@index_name' => $index_name]);
      return [];
    }
  }

}
