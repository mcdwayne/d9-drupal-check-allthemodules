<?php

namespace Drupal\monster_menus\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler which creates a chain of JOINs. This is useful when
 * a field needs to go through an intermediary table before the final
 * relationship.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("sequential_join")
 */
class SequentialJoin extends RelationshipPluginBase {

  /** @var ViewsHandlerManager $joinManager */
  var $joinManager;

  /**
   * Constructs an SequentialJoin object.
   *
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $this->ensureMyTable();

    $default_type = !empty($this->options['required']) ? 'INNER' : 'LEFT';
    $last_table = '';
    $base = isset($this->definition['base']) ? $this->definition['base'] : '';
    $relationship = isset($this->definition['relationship_table']) ? $this->definition['relationship_table'] : $this->relationship;
    foreach ($this->definition['joins'] as $join_def) {
      $definition = [
        'left_table' => $last_table ?: $join_def['left table'],
        'left_field' => $join_def['left field'],
        'table' => $join_def['table'],
        'field' => $join_def['field'],
        'type' => isset($join_def['type']) ? $join_def['type'] : $default_type,
        'adjusted' => TRUE,
      ];

      if (!empty($join_def['extra'])) {
        $definition['extra'] = $join_def['extra'];
      }

      $id = !empty($join_def['id']) ? $join_def['id'] : 'standard';
      $join = $this->joinManager->createInstance($id, $definition);
      $alias = $join_def['table'] . '_' . $join_def['field'];
      $base = $base ?: $definition['left_table'];
      $last_table = $this->query->addRelationship($alias, $join, $base, $relationship ?: $last_table);
      if (!$last_table) {
        throw new \Exception('Relationship to table ' . $relationship . ' could not be created.');
      }
    }
    $this->alias = $last_table;
  }

}
