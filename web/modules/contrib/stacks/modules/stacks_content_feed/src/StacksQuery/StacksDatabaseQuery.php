<?php
/**
 * @file
 * Contains \Drupal\stacks_content_feed\Grid\GridSolrQuery
 */

namespace Drupal\stacks_content_feed\StacksQuery;

use Drupal;
use Drupal\stacks\WidgetEntityInterface;

/**
 * Class GridSolrQuery
 *
 * Code that is shared by all grids goes here.
 */
class StacksDatabaseQuery extends StacksQueryBase {

  /**
   * If this grid is connected to a stacks entity, include that here.
   */
  protected $widget_entity = FALSE;

  /**
   * StacksDatabaseQuery constructor.
   * @param $unique_id
   */
  public function __construct($unique_id) {
    $this->unique_id = $unique_id;
  }

  /**
   * Queries the database for nodes.
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
   *
   * @returns (array): An array of node objects.
   */
  public function getNodeResults($options) {
    $query = Drupal::entityQuery('node');
    $query->condition('status', isset($options['status']) ? $options['status'] : 1);

    // Content Types.
    $group = $query->andConditionGroup();
    $uses_condition_group = FALSE;
    if (isset($options['content_types']) && $options['content_types']) {
      $group->condition('type', $options['content_types'], 'IN');
      $uses_condition_group = TRUE;
    }

    // Taxonomy Terms.
    if (isset($options['taxonomy']) && count($options['taxonomy']) > 0) {
      foreach ($options['taxonomy'] as $field_name => $tids) {
        if ($tids) {
          if ($field_name == 'ui_selected_tags') {
            if (isset($options['taxonomy_terms_field_names']) && is_array($options['taxonomy_terms_field_names']) && count($options['taxonomy_terms_field_names'])) {
              $group_or = $query->orConditionGroup();
              foreach ($options['taxonomy_terms_field_names'] as $fnames) {
                foreach ($fnames as $fname) {
                  $group_or->condition($fname, $tids, 'IN');
                }
              }
              $query->condition($group_or);
            }
            else {
              // Error condition.
              drupal_set_message(t("Selected content types do not have suitable taxonomy term fields. Ignoring taxonomy terms filter."), 'error');
            }
          }
          else {
            $group->condition($field_name, $tids, 'IN');
          }
        }
      }
    }

    // Text search.
    if (isset($options['search']) && !empty($options['search'])) {
      $group->condition('title', $options['search'], 'CONTAINS');
      $uses_condition_group = TRUE;
    }

    // Add hook to modify query.
    // Appends parameters invoking hook_widget_node_results_alter()
    $options['search_api_type'] = 'db';

    if ($uses_condition_group) {
      $query->condition($group);
    }

    // If pagination is not set to none, and if per_page is not set, set the
    // per_page value.
    if (!empty($options['pagination_type']) && empty($options['per_page'])) {
      $options['per_page'] = 10;
    }

    // Limit the number of results of the query.
    if ($options['per_page'] > 0) {

      // Pagination.
      $session = \Drupal::service('session');

      if (!$session->isStarted()) {
        $session->start();
      }

      $pager = $session->get('pager_elements');

      // Get query full count in session variable in order to fix page count
      // when loading page.
      $query_count = clone $query;
      $num_rows = $query_count->count()->execute();
      $pager[$this->unique_id] = ceil($num_rows / $options['per_page']);

      $session->set('pager_elements', $pager);

      // Getting page query to rebuild pager.
      $page = \Drupal::request()->query->get('page', '');

      // Rewriting page parameter to trigger pager refreshing:
      // check out https://api.drupal.org/api/drupal/core%21includes%21pager.inc/function/pager_find_page/8.2.x
      if (!empty($page)) {
        $page_array = [];

        for ($i = 0; $i < $this->unique_id; $i++) {
          $page_array[] = '';
        }
        $page_array[] = $page;

        \Drupal::request()->query->set('page', implode(',', $page_array));
      }
      else {
        $page = 0;
      }

      // Do not set element. It does not work.
      pager_default_initialize($num_rows, $options['per_page'], $this->unique_id);

      $query->pager($options['per_page'], $this->unique_id);
    }

    // Make sticky appear at the top?
    if (isset($options['sticky']) && $options['sticky']) {
      $query->sort('sticky', 'DESC');
    }

    // Should we only grab promoted content?
    if (isset($options['limit_by'])) {
      $this->getNodeResultsPromote($query, $options['limit_by']);
    }

    if (isset($options['order_by'])) {
      // Handle sorting.
      $this->getNodeResultsSort($query, $options['order_by']);
    }

    $query->addTag('stacks_contentfeed');
    if ($this->widget_entity) {
      $query->addMetaData('widget_entity', $this->widget_entity);
    }
    $nids = $query->execute();

    return $nids;
  }

  /**
   * Handles grabbing promoted content.
   *
   * @Todo: When custom_pub is working, confirm adding new options to the "field_cfeed_limit_by" field works properly.
   * @param $query
   * @param $limit_by
   */
  public function getNodeResultsPromote(&$query, $limit_by) {
    if (empty($limit_by)) {
      return;
    }

    $query->condition($limit_by, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(WidgetEntityInterface $entity) {
    $this->widget_entity = $entity;
  }
}
