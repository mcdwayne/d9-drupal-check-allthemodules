<?php

namespace Drupal\activity\Plugin\views\query;

use Drupal\activity\QueryActivity;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Query\Select;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Activity views query plugin which display all activities.
 *
 * @ViewsQuery(
 *   id = "activity",
 *   title = @Translation("All activities"),
 *   help = @Translation("Display all actions.")
 * )
 */
class ActivityPlugin extends QueryPluginBase {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The fields to SELECT.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * An array of stdClasses.
   *
   * @var array
   */
  protected $allItems = [];

  /**
   * An array for order the query.
   *
   * @var array
   */
  protected $orderBy = [];

  /**
   * A condition array for query.
   *
   * @var array
   */
  protected $where = [];

  /**
   * Store all actions.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $activities;

  /**
   * Object used to extract data from activity tables.
   *
   * @var \Drupal\activity\QueryActivity
   */
  protected $activityQuery;

  /**
   * Activity constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\activity\QueryActivity $activityQuery
   *   Query Activity service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueryActivity $activityQuery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activityQuery = $activityQuery;
    $this->activities = $this->activityQuery->getActivities();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('query_activity')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['accept_lang'] = [
      'default' => NULL,
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    if ($this->activities instanceof Select) {
      $this->activities->condition($field, $value, $operator);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addWhereExpression($group, $snippet, $args = []) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    ];
    if ($this->activities instanceof Select) {
      $this->activities->where($snippet, []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ensureTable($table, $relationship = NULL) {
    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $alias = '', $params = []) {
    $this->fields[$field] = $field;
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderBy($table, $field, $order = 'ASC', $alias = '', $params = []) {

    // Only ensure the table if it's not the special random key.
    // @todo: Maybe it would make sense to just add an addOrderByRand or something similar.
    if ($table && $table != 'rand') {
      $this->ensureTable($table);
    }

    // Only fill out this aliasing if there is a table;
    // otherwise we assume it is a formula.
    if (!$alias && $table) {
      $as = $table . '_' . $field;
    }
    else {
      $as = $alias;
    }

    if ($field) {
      $as = $this->addField($table, $field, $as, $params);
    }

    if ($this->activities instanceof Select) {
      $this->activities->orderBy($as, strtoupper($order));
    }

  }

  /**
   * Sets the allItems property.
   *
   * @param array $allItems
   *   An array of stdClasses.
   */
  public function setAllItems(array $allItems) {
    $this->allItems = $allItems;
  }

  /**
   * Implements Drupal\views\Plugin\views\query\QueryPluginBase::build().
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   */
  public function build(ViewExecutable $view) {
    $this->view = $view;

    $view->initPager();
    // Let the pager modify the query to add limits.
    $view->pager->query();
    // Clear cache in order to obtain the right result.
    Cache::invalidateTags(['config:views.view.' . $view->id()]);
    $view->build_info['query'] = $this->activities;
    $view->build_info['count_query'] = $this->activityQuery->countMessages();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    parent::execute($view);
    $count_query = $view->build_info['count_query'];
    $count_query->preExecute();

    // Build the count query.
    $count_query = $count_query->countQuery();
    try {
      if ($view->pager->useCountQuery() || !empty($view->get_total_rows)) {
        $view->pager->executeCountQuery($count_query);
      }
      $view->pager->preExecute($query);

      if ($this->activities instanceof Select) {
        if (!empty($this->limit) || !empty($this->offset)) {
          // We can't have an offset without a limit,
          // so provide a very large limit instead.
          $limit = intval(!empty($this->limit) ? $this->limit : 999999);
          $offset = intval(!empty($this->offset) ? $this->offset : 0);
          $this->activities->range($offset, $limit);
        }
        $result = $this->activities->execute();
        $result->setFetchMode(\PDO::FETCH_CLASS, 'Drupal\views\ResultRow');

        // Setup the result row objects.
        $view->result = iterator_to_array($result);
        array_walk($view->result, function (ResultRow $row, $index) {
          $row->index = $index;
        });

        $view->pager->postExecute($view->result);
        $view->pager->updatePageInfo();
        $view->total_rows = $view->pager->getTotalItems();

        $this->loadEntities($view->result);
      }

    }
    catch (DatabaseExceptionWrapper $e) {
      $view->result = [];
      if (!empty($view->live_preview)) {
        drupal_set_message($e->getMessage(), 'activity error view.');
      }
      else {
        throw new DatabaseExceptionWrapper("Exception in {$view->storage->label()}[{$view->storage->id()}]: {$e->getMessage()}");
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function match($element, $condition) {
    $value = $element[$condition['field']];
    switch ($condition['operator']) {
      case '=':
        return $value == $condition['value'];

      case 'IN':
        return in_array($value, $condition['value']);

    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'content' => ['ActivityPlugin'],
    ];
  }

}
