<?php

namespace Drupal\config_view\Plugin\views\query;

use Drupal\Component\Utility\Html;
use Drupal\config_view\Form\ConfigViewHelper;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Views query class for Config Entities.
 *
 * Reference: http://www.sitepoint.com/drupal-8-version-entityfieldquery/.
 *
 * @ViewsQuery(
 *   id = "config_view_query",
 *   title = @Translation("Configuration Entity"),
 *   help = @Translation("Configuration Entity Query")
 * )
 */
class ConfigViewQuery extends QueryPluginBase {

  /**
   * Number of results to display.
   *
   * @var int
   */
  protected $limit;

  /**
   * Offset of first displayed result.
   *
   * @var int
   */
  protected $offset;

  /**
   * The query that will be executed.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * Array of all encountered errors.
   *
   * Each of these is fatal, meaning that a non-empty $errors property will
   * result in an empty result being returned.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * Whether to abort or executing it.
   *
   * @var bool
   */
  protected $abort = FALSE;

  /**
   * The query's conditions representing the different Views filter groups.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * The logger to use for log messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * Stores the Helper object which handles the many_to_one complexity.
   *
   * @var \Drupal\views\ManyToOneHelper
   */
  protected $helper = NULL;

  /**
   * Config entity Id.
   *
   * @var string
   */
  protected $configEntityId;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id,
      $plugin_definition);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.factory')->get($plugin_id);
    $plugin->setLogger($logger);

    return $plugin;
  }

  /**
   * Retrieves the logger to use for log messages.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Sets the logger to use for log messages.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    try {
      parent::init($view, $display, $options);
      $this->configEntityId = $display->view->storage->get('base_table');
      $this->query = clone \Drupal::entityQuery($this->configEntityId);
      $this->query->addTag('views');
      $this->query->addTag('views_' . $view->id());
    }
    catch (\Exception $e) {
      $this->abort($e->getMessage());
    }
  }

  /**
   * Adds a field to the table.
   *
   * @param string|null $table
   *   Ignored.
   * @param string $field
   *   The combined property path of the property that should be retrieved.
   * @param string $alias
   *   (optional) Ignored.
   * @param array $params
   *   (optional) Ignored.
   *
   * @return string
   *   The name that this field can be referred to as (always $field).
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    $this->view = $view;

    if ($this->shouldAbort()) {
      return;
    }

    // Initialize the pager and let it modify the query to add limits.
    $view->initPager();
    $view->pager->query();
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ViewExecutable $view) {
    \Drupal::moduleHandler()->invokeAll('addWhere', array($view, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    if ($this->shouldAbort()) {
      if (error_displayable()) {
        foreach ($this->errors as $msg) {
          drupal_set_message(Html::escape($msg), 'error');
        }
      }
      $view->result = array();
      $view->total_rows = 0;
      $view->execute_time = 0;
      return;
    }

    try {
      // Trigger pager preExecute().
      $view->pager->preExecute($this->query);

      if (!$this->limit && $this->limit !== '0') {
        $this->limit = NULL;
      }
      // Set the range.
      $this->query->range($this->offset, $this->limit);

      $start = microtime(TRUE);

      // Execute the query.
      $this->query->andConditionGroup();
      $this->addConjunctionGroupandConditions();
      $results = $this->query->execute();

      // Store the results.
      $view->pager->total_items = $view->total_rows = count($results);
      if (!empty($view->pager->options['offset'])) {
        $view->pager->total_items -= $view->pager->options['offset'];
      }

      $view->result = array();
      if (!empty($results)) {
        $this->addResults($results, $view);
      }
      $view->execute_time = microtime(TRUE) - $start;

      // Trigger pager postExecute().
      $view->pager->postExecute($view->result);
      $view->pager->updatePageInfo();
    }
    catch (\Exception $e) {
      $this->abort($e->getMessage());
      // Recursion to get the same error behaviour as above.
      $this->execute($view);
    }
  }

  /**
   * Aborts the query.
   *
   * Used by handlers to flag a fatal error which shouldn't be displayed but
   * still lead to the view returning empty and the query not being executed.
   *
   * @param string|null $msg
   *   Optionally, a translated, unescaped error message to display.
   */
  public function abort($msg = NULL) {
    if ($msg) {
      $this->errors[] = $msg;
    }
    $this->abort = TRUE;
  }

  /**
   * Checks whether this query should be aborted.
   *
   * @return bool
   *   TRUE if the query should/will be aborted, FALSE otherwise.
   */
  public function shouldAbort() {
    return $this->abort;
  }

  /**
   * Sets the query.
   *
   * If this method is not called on the query before execution.
   *
   * @param string|array|null $keys
   *   A string with the search keys.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::keys()
   */
  public function keys($keys = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->keys($keys);
    }
    return $this;
  }

  /**
   * Sets the fields.
   *
   * @param array $fields
   *   An array containing fulltext fields that should be searched.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::setFulltextFields()
   */
  public function setFulltextFields($fields = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->setFulltextFields($fields);
    }
    return $this;
  }

  /**
   * Adds a new ($field $operator $value) condition filter.
   *
   * @param string $field
   *   The field to filter on, e.g. 'title'.
   * @param mixed $value
   *   The value the field should have (or be related to by the operator).
   * @param string $operator
   *   The operator to use for checking the constraint. The following operators
   *   are supported for primitive types: "=", "<>", "<", "<=", ">=", ">". They
   *   have the same semantics as the corresponding SQL operators.
   *   If $field is a fulltext field, $operator can only be "=" or "<>", which
   *   are in this case interpreted as "contains" or "doesn't contain",
   *   respectively.
   *   If $value is NULL, $operator also can only be "=" or "<>", meaning the
   *   field must have no or some value, respectively.
   * @param string|null $group
   *   (optional) The Views query filter group to add this filter to.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::addCondition()
   */
  public function addCondition($field, $value, $operator = '=', $group = NULL) {
    if (!$this->shouldAbort()) {
      // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
      // the default group.
      if (empty($group)) {
        $group = 0;
      }
      $this->query->condition($this->sanitizeFieldId($field), $value, $this->sanitizeOperator($operator));
    }
    return $this;
  }

  /**
   * Adds a simple condition to the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the EntityFieldQuery Interface.
   *
   * @param int $group
   *   The condition group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param string $field
   *   The ID of the field to check; or a filter object to add to the query; or,
   *   for compatibility purposes, a database condition object to transform into
   *   a search filter object and add to the query. If a field ID is passed and
   *   starts with a period (.), it will be stripped.
   * @param mixed $value
   *   (optional) The value the field should have (or be related to by the
   *   operator). Or NULL if an object is passed as $field.
   * @param string|null $operator
   *   (optional) The operator to use for checking the constraint. The following
   *   operators are supported for primitive types: "=", "<>", "<", "<=", ">=",
   *   ">". They have the same semantics as the corresponding SQL operators.
   *   If $field is a fulltext field, $operator can only be "=" or "<>", which
   *   are in this case interpreted as "contains" or "doesn't contain",
   *   respectively.
   *   If $value is NULL, $operator also can only be "=" or "<>", meaning the
   *   field must have no or some value, respectively.
   *   To stay compatible with Views, "!=" is supported as an alias for "<>".
   *   If an object is passed as $field, $operator should be NULL.
   *
   * @return $this
   *
   * @code
   *   $this->query->
   *       ->condition($field, $value, 'NOT IN')
   *       ->condition($field, $value, 'IS NULL')
   *   );
   * @endcode
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    if ($this->shouldAbort()) {
      return $this;
    }

    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all the
    // default group.
    if (empty($group)) {
      $group = 0;
    }

    if (is_array($field)) {
      foreach ($field->conditions() as $cond) {
        $condition = [
          $this->sanitizeFieldId($cond->field),
          $this->sanitizeValue($cond->value),
          $this->sanitizeOperator($cond->operator, $cond->value),
        ];
        if (!($operator === 'LIKE' && $value === '')) {
          $this->conditions[$group]['conditions'][] = $condition;
        }
      }
    }
    else {
      $condition = [
        is_array($value) ? 'id' : $this->sanitizeFieldId($field),
        $this->sanitizeValue($value),
        $this->sanitizeOperator($operator, $value),
      ];
      if (!($operator === 'LIKE' && $value === '')) {
        $this->conditions[$group]['conditions'][] = $condition;
      }
    }
    return $this;
  }

  /**
   * Removes % character.
   *
   * @param string $value
   *   Drupal adds % when operator LIKE is used.
   *
   * @return mixed
   *   Remove % from the query value.
   */
  protected function sanitizeValue($value) {
    return str_replace('%', '', $value);
  }

  /**
   * Adapts a field ID for use in a EntityFieldQuery query.
   *
   * This method will remove a leading period (.), if present. This is done
   * because in the SQL Views query plugin field IDs are always prefixed with a
   * table alias (in our case always empty) followed by a period.
   *
   * @param string $field_id
   *   The field ID to adapt for use in the EntityFieldQuery.
   *
   * @return string
   *   The sanitized field ID.
   *
   * @see Drupal\config_view\Plugin\views\filter::getValues()
   */
  protected function sanitizeFieldId($field_id) {
    if ($field_id && $field_id[0] === '.') {
      $field_id = substr($field_id, 1);
    }

    return $field_id;
  }

  /**
   * Adapts an operator for use in a EntityFieldQuery query.
   *
   * This method maps Views' "!=" to the "<>" EntityFieldQuery uses.
   *
   * @param string $operator
   *   The operator to adapt for use in the EntityFieldQuery.
   *
   * @return string
   *   The sanitized operator.
   */
  protected function sanitizeOperator($operator, $value = NULL) {
    if ($operator === '!=') {
      $operator = '<>';
    }

    if ($operator === 'LIKE') {
      if (count_chars($value, 1)[42] == 2) {
        $operator = 'CONTAINS';
      }
      elseif (strpos($value, '%') === 0) {
        $operator = 'ENDS_WITH';
      }
      else {
        $operator = 'STARTS_WITH';
      }
    }
    return $operator;
  }

  /**
   * Adds a sort directive to the query.
   *
   * If no sort is manually set, the results will be sorted descending by
   * relevance.
   *
   * @param string $field
   *   Field to sort by.
   * @param string $order
   *   The order to sort items in - either 'ASC' or 'DESC'.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::sort()
   */
  public function sort($field, $order = 'ASC') {
    if (!$this->shouldAbort()) {
      $this->query->sort($field, $order);
    }
    return $this;
  }

  /**
   * Adds a range of results to return.
   *
   * This will be saved in the query's options. If called without parameters,
   * this will remove all range restrictions previously set.
   *
   * @param int|null $offset
   *   The zero-based offset of the first result returned.
   * @param int|null $limit
   *   The number of results to return.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::range()
   */
  public function range($offset = NULL, $limit = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->range($offset, $limit);
    }
    return $this;
  }

  /**
   * Retrieves the keys for this query.
   *
   * @return array|string|null
   *   This object's search keys - either a string or an array specifying a
   *   complex query expression.
   *   An array will contain a '#conjunction' key specifying the conjunction
   *   type, and query strings or nested expression arrays at numeric keys.
   *   Additionally, a '#negation' key might be present, which means – unless it
   *   maps to a FALSE value – that the keys contained in that array
   *   should be negated, i.e. not be present in returned results. The negation
   *   works on the whole array, not on each contained term individually – i.e.,
   *   with the "AND" conjunction and negation, only results that contain all
   *   the terms in the array should be excluded; with the "OR" conjunction and
   *   negation, all results containing one or more of the terms in the array
   *   should be excluded.
   *
   * @see keys()
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getKeys()
   */
  public function &getKeys() {
    if (!$this->shouldAbort()) {
      return $this->query->getKeys();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves the search keys.
   *
   * @return array|string|null
   *   The unprocessed search keys, exactly as passed to this object. Has the
   *   same format as the return value of getKeys().
   *
   * @see keys()
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getOriginalKeys()
   */
  public function getOriginalKeys() {
    if (!$this->shouldAbort()) {
      return $this->query->getOriginalKeys();
    }
    return NULL;
  }

  /**
   * Retrieves the fulltext fields.
   *
   * @return string[]|null
   *   An array containing the fields.
   *
   * @see setFulltextFields()
   * @see \Drupal\Core\Entity\Query\QueryInterface::getFulltextFields()
   */
  public function &getFulltextFields() {
    if (!$this->shouldAbort()) {
      return $this->query->getFulltextFields();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves the filter object associated with the query.
   *
   * @return ConditionGroupInterface|null
   *   Returns the filter.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getConditionGroup()
   */
  public function getFilter() {
    if (!$this->shouldAbort()) {
      return $this->query->getConditionGroup();
    }
    return NULL;
  }

  /**
   * Retrieves the sorts set for this query.
   *
   * @return array
   *   An array specifying the sort order for this query. Array keys are the
   *   field names in order of importance, the values are the respective order
   *   in which to sort the results according to the field.
   *
   * @see sort()
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getSorts()
   */
  public function &getSort() {
    if (!$this->shouldAbort()) {
      return $this->query->getSorts();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves an option set on the query.
   *
   * @param string $name
   *   The name of an option.
   * @param mixed $default
   *   The value to return if the specified option is not set.
   *
   * @return mixed
   *   The value of the option with the specified name, if set. NULL otherwise.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getOption()
   */
  public function getOption($name, $default = NULL) {
    if (!$this->shouldAbort()) {
      return $this->query->getOption($name, $default);
    }
    return $default;
  }

  /**
   * Sets an option for the query.
   *
   * @param string $name
   *   The name of an option. The following options are recognized by default.
   * @param mixed $value
   *   The new value of the option.
   *
   * @return mixed
   *   The option's previous value, or NULL if none was set.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::setOption()
   */
  public function setOption($name, $value) {
    if (!$this->shouldAbort()) {
      return $this->query->setOption($name, $value);
    }
    return NULL;
  }

  /**
   * Retrieves all options set for the query.
   *
   * The return value is a reference to the options so they can also be altered
   * this way.
   *
   * @return array
   *   An associative array of query options.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::getOptions()
   */
  public function &getOptions() {
    if (!$this->shouldAbort()) {
      return $this->query->getOptions();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Ensures a table exists in the query.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable() {
    return '';
  }

  /**
   * Adds result items to a view's result set.
   *
   * @param array $results
   *   The search results.
   * @param \Drupal\views\ViewExecutable $view
   *   The executed view.
   */
  protected function addResults(array $results, ViewExecutable $view) {
    // Views \Drupal\views\Plugin\views\style\StylePluginBase::renderFields()
    // uses a numeric results index to key the rendered results.
    // The ResultRow::index property is the key then used to retrieve these.
    $count = 0;
    $entity_type = ConfigViewHelper::getMapping($this->configEntityId);

    foreach ($results as $result) {
      $values = array();
      $object = \Drupal::entityManager()->getStorage($this->configEntityId)->load($result);
      $values['_relationship_objects'][NULL] = array($object);

      foreach ($entity_type as $key => $value) {
        if (isset($value['label'])) {
          $values[$key] = ConfigViewHelper::responseToString($object->get($key));
        }
      }
      $values['index'] = $count++;
      $view->result[] = new ResultRow($values);
    }
  }

  /**
   * Adds an ORDER BY clause to the query.
   *
   * @param string|null $table
   *   The table this field is part of.
   * @param string|null $field
   *   (optional) The field or formula to sort on. If already a field, enter
   *   NULL and put in the alias.
   * @param string $order
   *   (optional) Either ASC or DESC.
   * @param string $alias
   *   (optional) The alias to add the field as.
   * @param array $params
   *   (optional) Any parameters that should be passed through to the addField()
   *   call.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addOrderBy()
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
    $this->sort($field, $order);
  }

  /**
   * Create a new grouping for the WHERE or HAVING clause.
   *
   * @param string $type
   *   Either 'AND' or 'OR'. All items within this group will be added
   *   to the WHERE clause with this logical operator.
   * @param string $group
   *   An ID to use for this group. If unspecified, an ID will be generated.
   * @param string $where
   *   Where or 'having'.
   *
   * @return string $group
   *   The group ID generated.
   */
  public function setWhereGroup($type = 'AND', $group = NULL, $where = 'where') {
    // Set an alias.
    $groups = &$this->$where;

    if (!isset($group)) {
      $group = empty($groups) ? 1 : max(array_keys($groups)) + 1;
    }

    // Create an empty group.
    if (empty($groups[$group])) {
      $groups[$group] = array('conditions' => array(), 'args' => array());
    }

    $groups[$group]['type'] = strtoupper($type);
    return $group;
  }

  /**
   * Control how all WHERE and HAVING groups are put together.
   *
   * @param string $type
   *   Either 'AND' or 'OR'.
   */
  public function setGroupOperator($type = 'AND') {
    $this->groupOperator = strtoupper($type);
  }

  /**
   * Converts relational query conditions into Query Entity.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   When correct operator is not provided.
   */
  private function addConjunctionGroupandConditions() {
    $this->query->conjunction = $this->groupOperator;

    foreach ($this->conditions as $gr => $cnd) {

      if ($this->where[$gr]['type'] == 'OR') {
        $conditions = $this->query->orConditionGroup();
      }
      else {
        $conditions = $this->query->andConditionGroup();
      }

      foreach ($cnd['conditions'] as $sc) {
        $conditions->condition($sc[0], $sc[1], $sc[2]);
      }
      $this->query->condition($conditions);
    }
  }

}
