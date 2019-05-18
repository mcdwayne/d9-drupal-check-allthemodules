<?php

namespace Drupal\evergreen_node\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\evergreen\EvergreenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\join\JoinPluginInterface;

/**
 * Filter by evergreen status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("node_is_evergreen")
 */
class NodeIsEvergreen extends BooleanOperator {

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

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [
      1 => $this->t('Yes'),
      0 => $this->t('No, perishable content only'),
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
        $conditions['default_evergreen'][] = $config['bundle'];
        continue;
      }
      $conditions['default_expires'][] = $config['bundle'];
    }

    // only have configurations that default to evergreen, so we can search for
    // nodes that:
    //   have a content entity with the status set to evergreen
    //   OR
    //   do not have an evergreen content entity (so it uses the default)
    if ($conditions['default_evergreen'] && !$conditions['default_expires']) {
      $this->addConditionsFor($conditions['default_evergreen'], $field, $query_operator, EVERGREEN_STATUS_EVERGREEN, $join);
    }
    // only have configurations that default to expires
    elseif ($conditions['default_expires'] && !$conditions['default_evergreen']) {
      $this->addConditionsFor($conditions['default_expires'], $field, $query_operator, 0, $join);
    }
    // have both configuration types...
    elseif ($conditions['default_expires'] && $conditions['default_evergreen']) {
      // we need to loop through both sets of conditions to create specific
      // where statements that can handle this...
      $all_conditions_group = (new Condition('OR'));
      foreach ($conditions['default_expires'] as $bundle) {
        $condition = $this->addConditionFor($bundle, $field, $query_operator, 0, $join, ['addToQuery' => FALSE]);
        $all_conditions_group->condition($condition);
      }
      foreach ($conditions['default_evergreen'] as $bundle) {
        $condition = $this->addConditionFor($bundle, $field, $query_operator, EVERGREEN_STATUS_EVERGREEN, $join, ['addToQuery' => FALSE]);
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

    // add conditions for this value/default pairing. this breaks down into the
    // following:
    //
    // ---------------------------------------------
    // | value | default | rules                   |
    // ---------------------------------------------
    // | 1     | 1       | no content entity       |
    // |       |         | OR                      |
    // |       |         | content entity set to 1 |
    // ---------------------------------------------
    // | 1     | 0       | content entity set to 1 |
    // ---------------------------------------------
    // | 0     | 1       | content entity set to 0 |
    // ---------------------------------------------
    // | 0     | 0       | no content entity       |
    // |       |         | OR                      |
    // |       |         | content entity set to 0 |
    // ---------------------------------------------
    //
    // value = 1 and default = 1
    if ($this->value && $default == EVERGREEN_STATUS_EVERGREEN) {
      // has no content entity OR content entity set to EVERGREEN_STATUS_EVERGREEN
      $condition = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', EVERGREEN_STATUS_EVERGREEN, '=')
        ->condition($field, $bundle, '=');
      $group->condition($condition);

      $condition2 = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', NULL, 'IS');
      $group->condition($condition2);
    }
    // value = 1 and default = 0
    elseif ($this->value && $default != EVERGREEN_STATUS_EVERGREEN) {
      // must have a content entity and be set to evergreen
      $condition = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', EVERGREEN_STATUS_EVERGREEN, '=')
        ->condition($field, $bundle, '=');
      $group->condition($condition);
    }
    // value = 0 and default = 1
    elseif (!$this->value && $default == EVERGREEN_STATUS_EVERGREEN) {
      $condition = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', 0, '=')
        ->condition($field, $bundle, '=');
      $group->condition($condition);
    }
    // value = 0 and default = 0
    elseif (!$this->value && $default == 0) {
      $condition = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', 0, '=')
        ->condition($field, $bundle, '=');
      $group->condition($condition);

      $condition2 = (new Condition('AND'))
        ->condition($join->table . '.evergreen_status', NULL, 'IS NULL');
      $group->condition($condition2);
    }

    if ($options['addToQuery']) {
      $this->query->addWhere($this->options['group'], $group);
    }
    return $group;
  }

}
