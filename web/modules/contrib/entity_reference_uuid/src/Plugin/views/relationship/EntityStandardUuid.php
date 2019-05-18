<?php

namespace Drupal\entity_reference_uuid\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\Standard;
use Drupal\views\Views;

/**
 * Implementation of a relationship plugin for UUID.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_standard_uuid")
 */
class EntityStandardUuid extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Figure out what base table this relationship brings to the party.
    $table_data = Views::viewsData()->get($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    // Unset provider to avoid duplicates.
    unset($table_data['table']['provider']);

    $this->ensureMyTable();

    $def = $this->definition;

    // The 'entity base table' is e.g. {node}.
    $def['table'] = $this->definition['entity base table'];
    // The 'entity uuid field' is e.g. {node}.uuid.
    $def['field'] = $this->definition['entity uuid field'];
    // This is the entity_reference_uuid field table like {node__field_foo}.
    $def['left_table'] = $this->table;
    // This is the 'relationship field' like field_foo_target_uuid.
    $def['left_field'] = $this->realField;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }
    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }
    // Join first from e.g. {node__field_foo}.field_foo_target_uuid to
    // {node}.uuid.
    $l_join = Views::pluginManager('join')->createInstance($id, $def);

    // Use a short alias for this:
    $first_alias = $this->definition['entity base table'] . '_' . $this->table . '_' . $this->realField;
    // And now add our table, using the new relationship if one was used.
    $alias = $this->query->addTable($this->definition['entity base table'], $this->relationship, $l_join, $first_alias);

    // If there is no data table, the next join is not needed.
    if ($this->definition['base'] !== $this->definition['entity base table']) {
      $def = $this->definition;
      // The data table e.g. {node_field_data}.
      $def['table'] = $this->definition['base'];
      // The entity ID field e.g. {node_field_data}.nid.
      $def['field'] = $base_field;
      // The alias from the first join to the entity base table e.g. {node}.
      $def['left_table'] = $alias;
      // We again use the base field to connect the base and data tables.
      $def['left_field'] = $base_field;
      $def['adjusted'] = TRUE;
      if (!empty($this->options['required'])) {
        $def['type'] = 'INNER';
      }
      // Join next from e.g. {node}.nid to {node_field_data}.nid. This is needed
      // since uuid is not in the data table.
      $join = Views::pluginManager('join')->createInstance($id, $def);

      // Use a short alias for this:
      $alias = $def['table'] . '_' . $this->table . '_' . $this->realField;
    }
    else {
      $join = $l_join;
    }

    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);

    // Add access tags if the base table provide it.
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->addTag($access_tag);
    }
  }

}
