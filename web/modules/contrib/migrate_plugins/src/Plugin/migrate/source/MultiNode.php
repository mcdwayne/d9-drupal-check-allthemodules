<?php

namespace Drupal\migrate_plugins\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Provides a 'MultiNode' migrate source.
 *
 * @MigrateSource(
 *   id = "d7_multi_node",
 *   source_module = "node"
 * )
 */
class MultiNode extends Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    $entity_id = $row->getSourceProperty('nid');
    $settings_defaults = [
      'entity_type' => 'node',
      'field_column_name' => 'entity_id',
      'group_column_name' => 'gid',
    ];

    // Get all entities referencing current row node using
    // the specified relationships fields.
    if (isset($this->configuration['relationships'])) {
      foreach ($this->configuration['relationships'] as $name => $settings) {
        // Skip relationships lookup if settings are incorrectly configured.
        if (!isset($settings['field_name'])) {
          continue;
        }

        // Set the default settings.
        $settings += $settings_defaults;

        // Find referencing entities via relationship fields.
        $entities = $this->getReferencingEntities($settings, $entity_id);
        // Attach the full entities to a property named as the relationship
        // field that we used to found the referencing entities.
        $row->setSourceProperty($name, $entities);
      }
    }
  }

  /**
   * Find entities referencing current row node entity.
   *
   * An entity could be referencing a node via a standard entityreference field
   * which follow the field_data_* table naming convention or with special OG
   * references via the og_membership field.
   *
   * @param array $settings
   *   The relationship settings: field and column.
   * @param int $entity_id
   *   The ID of the referencing entity.
   */
  protected function getReferencingEntities(array $settings, $entity_id) {
    $referencing_entities = [];

    // Get references via field_data table.
    $table = 'field_data_' . $settings['field_name'];
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $settings['entity_type'])
      ->condition($settings['field_column_name'], $entity_id)
      ->condition('deleted', 0);

    foreach ($query->execute() as $row) {
      // By default assume is a normal reference.
      $value = $row['entity_id'];
      // For reverse reference that lookup via target column we can use the
      // column name setting.
      if (strpos($settings['field_column_name'], '_target_id') === FALSE) {
        // Find the target_id value column.
        $column_names = array_keys($row);
        $matches = preg_grep("/_target_id/", $column_names);
        $column_name = array_values($matches)[0];
        $value = $row[$column_name];
      }

      $referencing_entities[$value] = $row['entity_type'];
    }

    // Get references via og_membership table.
    $query = $this->select('og_membership', 't')
      ->fields('t')
      ->condition('group_type', $settings['entity_type'])
      ->condition('field_name', $settings['field_name'])
      ->condition($settings['group_column_name'], $entity_id);

    foreach ($query->execute() as $row) {
      // By default assume is a normal reference.
      $value = $row['gid'];
      // For reverse reference use etid.
      if ($settings['group_column_name'] == 'gid') {
        $value = $row['etid'];
      }

      $referencing_entities[$value] = $row['entity_type'];
    }

    // Array for all referencing entities on current field.
    $loaded_entities = [];

    // Process the entity references load and attach as row fields.
    foreach ($referencing_entities as $referencing_entity_id => $entity_type) {
      switch ($entity_type) {
        case 'node':
          $loaded_entities[] = $this->loadNode($referencing_entity_id);
          break;

        case 'user':
          $loaded_entities[] = $this->loadUser($referencing_entity_id);
          break;

        default:
          echo "No entity load handling is implemented for '{$entity_type}'.";
          break;
      }
    }

    return $loaded_entities;
  }

  /**
   * Load the D7 node entity including Field API fields.
   *
   * @param int $entity_id
   *   The referencing entity ID to load.
   */
  public function loadNode($entity_id) {
    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')
      ->fields('n', [
        'nid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'sticky',
        'tnid',
        'translate',
      ])
      ->fields('nr', [
        'vid',
        'title',
        'log',
        'timestamp',
      ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);
    $query->condition('n.nid', $entity_id);

    // If the content_translation module is enabled, get the source langcode
    // to fill the content_translation_source field.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $query->leftJoin('node', 'nt', 'n.tnid = nt.nid');
      $query->addField('nt', 'language', 'source_langcode');
    }
    $this->handleTranslations($query);

    // Fetch the entity base properties.
    $result = $query->execute();
    $entity = $result->fetchAssoc();

    if (!$entity) {
      return FALSE;
    }

    // Load the Field API entity fields.
    foreach (array_keys($this->getFields('node', $entity['type'])) as $field) {
      $entity[$field] = $this->getFieldValues('node', $field, $entity['nid'], $entity['vid']);
    }

    // Load the Feed Source on feed_source content type.
    if ($entity['type'] == 'feed_source') {
      $query = $this->select('feeds_source', 'fs')
        ->fields('fs')
        ->condition('feed_nid', $entity['nid']);

      // Fetch the entity base properties.
      $result = $query->execute();
      $feed_source = $result->fetchAssoc();

      if ($feed_source) {
        $entity['feed'] = $feed_source;
      }
    }

    return $entity;
  }

  /**
   * Load the D7 user entity including Field API fields.
   *
   * @param int $entity_id
   *   The referencing entity ID to load.
   */
  public function loadUser($entity_id) {
    // Select node in its last revision.
    $query = $this->select('users', 'u')
      ->fields('u')
      ->condition('uid', $entity_id);

    // Fetch the entity base properties.
    $result = $query->execute();
    $entity = $result->fetchAssoc();

    if (!$entity) {
      return FALSE;
    }

    // Load the Field API entity fields.
    foreach (array_keys($this->getFields('user', $entity['type'])) as $field) {
      $entity[$field] = $this->getFieldValues('user', $field, $entity['uid']);
    }

    return $entity;
  }

}
