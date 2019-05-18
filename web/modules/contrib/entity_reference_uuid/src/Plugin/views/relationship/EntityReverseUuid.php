<?php

namespace Drupal\entity_reference_uuid\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of a reverse relationship plugin for UUID.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_reverse_uuid")
 */
class EntityReverseUuid extends RelationshipPluginBase {

  /**
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * Override to document the type here for better code completion.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * Constructs an EntityReverseUuid object.
   *
   * @see \Drupal\views\Plugin\views\relationship\EntityReverse
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
   * {@inheritdoc}
   */
  public function query() {

    $this->ensureMyTable();

    // If there is no data table for the target entity, we don't need the
    // extra join to get to the UUID.
    if ($this->definition['target base'] == $this->definition['target entity base table']) {
      $first_alias = $this->definition['target entity base table'];
    }
    else {
      // First, relate the target entity base table to its data table. This
      // allows us to find the UUID which is not in the data table.
      $first = [
        // This would be e.g. {node_field_data}.
        'left_table' => $this->definition['target base'],
        // This would be e.g. {node_field_data}.nid.
        'left_field' => $this->definition['target entity base field'],
        // This would be e.g. {node}.
        'table' => $this->definition['target entity base table'],
        // This would be e.g. {node}.nid.
        'field' => $this->definition['target entity base field'],
        'adjusted' => TRUE,
        // The data table can always be joined to its base table.
        'type' => 'INNER',
      ];

      if (!empty($this->definition['join_id'])) {
        $id = $this->definition['join_id'];
      }
      else {
        $id = 'standard';
      }
      /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $first_join */
      $first_join = $this->joinManager->createInstance($id, $first);
      $first_alias = $this->query->addTable($this->definition['target entity base table'], $this->relationship, $first_join);
    }

    // Second, relate the target entity base table UUID field to the reference
    // field's target_uuid column.
    $second = [
      // This would be e.g. {node}.
      'left_table' => $first_alias,
      // This would be e.g. {node}.uuid.
      'left_field' => $this->definition['target entity uuid field'],
      // The table containing the reference field like {node__field_foo}.
      'table' => $this->definition['field table'],
      // This is the uuid field like {node__field_foo}.field_foo_target_uuid.
      'field' => $this->definition['field field'],
    ];

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }
    // This is populated for field APi fields as an extra join condition to
    // insure that query does not find deleted fields.
    if (!empty($this->definition['join_extra'])) {
      $second['extra'] = $this->definition['join_extra'];
    }
    if (!empty($this->definition['join_id'])) {
      $id = $this->definition['join_id'];
    }
    else {
      $id = 'standard';
    }
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $second_join */
    $second_join = $this->joinManager->createInstance($id, $second);
    // Define alias?
    $second_alias = $this->query->addTable($this->definition['field table'], $this->relationship, $second_join);


    // Skip this when the field is in the entity base (or data) table.
    if ($this->definition['base'] === $this->definition['field table']) {
      $third_alias = $second_alias;
      $third_join = $second_join;
    }
    else {
      // Third, relate the reference field table to the entity using
      // the entity id on the field table and the entity's id (base) field on
      // its data table.
      $third = [
        // The table containing the reference field like {node__field_foo}.
        'left_table' => $second_alias,
        // Hard coded field name here based on field API.
        'left_field' => 'entity_id',
        // The data table for the entity with the field e.g. {node_field_data}.
        'table' => $this->definition['base'],
        // This would be e.g. {node_field_data}.nid.
        'field' => $this->definition['base field'],
        'adjusted' => TRUE
      ];

      if (!empty($this->options['required'])) {
        $third['type'] = 'INNER';
      }

      if (!empty($this->definition['join_id'])) {
        $id = $this->definition['join_id'];
      }
      else {
        $id = 'standard';
      }
      /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $third_join */
      $third_join = $this->joinManager->createInstance($id, $third);
      $third_join->adjusted = TRUE;
      // Define alias?
      $third_alias = $this->query->addTable($this->definition['base'], $this->relationship, $third_join);
    }

    $this->alias = $this->query->addRelationship($third_alias, $third_join, $this->definition['base'], $this->relationship);
  }

}
