<?php

namespace Drupal\evergreen_node\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\evergreen\EvergreenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\join\JoinPluginInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Filter if the node is expired.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("node_expired")
 */
class NodeExpired extends BooleanOperator {

  /**
   * Evergreen service.
   *
   * @var Drupal\evergreen\EvergreenService
   */
  protected $evergreen;

  /**
   * Constructs a NodeIsEvergreen object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EvergreenServiceInterface $evergreen) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->definition['type'] = 'yes-no';
    $this->evergreen = $evergreen;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('evergreen')
    );
  }

  public function ensureMyTable() {
    $alias = parent::ensureMyTable();
    // $this->changedField = $this->query->addField('node', 'changed');
    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [
      1 => $this->t('Yes'),
      0 => $this->t('No'),
    ];
  }

  /**
   * Override queryOpBoolean() so we can inject evergreen functionality.
   */
  public function queryOpBoolean($field, $query_operator = self::EQUAL) {
    $table = $this->ensureMyTable();
    $join = $this->getTableJoin('evergreen_content', $table);

    if (!$join) {
      return;
    }

    $clone = clone $join;
    $this->query->addTable($join->table, NULL, $clone);

    // we need to figure out the default configuration for nodes. since we
    // can't know if the view is limited by bundle, we just have to work off
    // all configured nodes...
    $node_configurations = $this->evergreen->getConfiguredEntityTypes('node');

    // we will add the configured bundles into a "default" bucket that matches
    // their default evergreen status.
    $conditions = [
      'default_evergreen' => [],
      'default_expires' => [],
    ];
    foreach ($node_configurations as $config) {
      if ($config['default_status'] == EVERGREEN_STATUS_EVERGREEN) {
        $conditions['default_evergreen'][] = $config;
        continue;
      }
      $conditions['default_expires'][] = $config;
    }

    // we have two cases to consider:
    //
    // 1. node has a content entity and it is set to expire or
    // 2. there is no content entity and the default is expiration (and it is
    //    expired based on the changed time)
    $time = time();
    if ($conditions['default_evergreen'] && !$conditions['default_expires']) {
      // we only have content that defaults to evergreen, so we only need to
      // find content with a content entity that is expired
      $this->addConditionsFor($conditions['default_evergreen'], $field, $query_operator, $time, $join);
    }
    elseif ($conditions['default_expires'] && !$conditions['default_evergreen']) {
      // we only have content that defaults to expires so we need to find
      // matching content entity or no content entity + the expired time plus
      // changed time is expired
      $this->addConditionsFor($conditions['default_expires'], $field, $query_operator, $time, $join);
    }
    else {
      // we have a mix of content so we need to create specific conditions for
      // each.
      $all_conditions_group = (new Condition('OR'));
      foreach ($conditions['default_expires'] as $bundle) {
        $condition = $this->addConditionFor($bundle, $field, $query_operator, $time, $join, ['addToQuery' => FALSE]);
        $all_conditions_group->condition($condition);
      }
      foreach ($conditions['default_evergreen'] as $bundle) {
        $condition = $this->addConditionFor($bundle, $field, $query_operator, $time, $join, ['addToQuery' => FALSE]);
        $all_conditions_group->condition($condition);
      }
      $this->query->addWhere($this->options['group'], $all_conditions_group);
    }

  }

  /**
   * Add a condition for all of configured bundles.
   *
   * @see queryOpBoolean()
   */
  protected function addConditionsFor($conditions, $field, $query_operator, $default, JoinPluginInterface $join) {
    foreach ($conditions as $condition) {
      $this->addConditionFor($condition, $field, $query_operator, $default, $join);
    }
  }

  /**
   * Add a condition for this query.
   *
   * Conditions for checking if a node is evergreen or not means that we need
   * to assess:
   *
   * 1. The default for this entity/bundle.
   * 2. Whether or not the node has a content entity that specifies something
   *    besides the default.
   *
   * If a node has a content entity, we can just use that value, but if not we
   * need to include information for the default setting.
   *
   * @see queryOpBoolean()
   * @see addConditionsFor()
   */
  protected function addConditionFor($bundle, $field, $query_operator, $default, JoinPluginInterface $join, array $options = []) {
    $default_options = [
      'addToQuery' => TRUE,
    ];
    $options = array_merge($default_options, $options);

    $group = (new Condition('OR'));

    if ($bundle['default_status'] == 1) {
      // content is by default evergreen, so we need to find content with an
      // expired content entity.
      $condition = new Condition('AND');

      // if true, we want expired nodes
      if ($this->value) {
        $condition->condition($join->table . '.evergreen_expires', $default, '<=');
      }
      else {
        $condition->condition($join->table . '.evergreen_expires', $default, '>');
      }

      $group->condition($condition);
    }
    else {
      $condition = new Condition('AND');
      $default_expiration = intval($default) - intval($bundle['default_expiry']);
      if ($this->value) {
        $condition
          ->condition($join->table . '.evergreen_expires', $default, '<=')
          ->condition('node_field_data.changed', $default_expiration, '<=');
      }
      else {
        $condition
          ->condition($join->table . '.evergreen_expires', $default, '>')
          ->condition('node_field_data.changed', $default_expiration, '>');
      }

      $group->condition($condition);
    }

    if ($options['addToQuery']) {
      $this->query->addWhere($this->options['group'], $group);
    }
    return $group;
  }

}
