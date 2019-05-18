<?php

namespace Drupal\prev_next;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\node\Entity\NodeType;

/**
 * Defines an PrevNextApi service.
 */
class PrevNextApi implements PrevNextApiInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The PrevNextHelper service.
   *
   * @var \Drupal\prev_next\PrevNextHelperInterface
   */
  protected $prevnextHelper;

  /**
   * Constructs an PrevNextHelper object.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\prev_next\PrevNextHelperInterface $prevnext_helper
   *   The PrevNextHelper service.
   */
  public function __construct(ModuleHandler $module_handler, Connection $database, PrevNextHelperInterface $prevnext_helper) {
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->prevnextHelper = $prevnext_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function add($entity_id, $bundle_name) {

    $prevnext_bundle = $this->prevnextHelper->loadBundle($bundle_name);
    $search_criteria = $prevnext_bundle->get('indexing_criteria');
    $cond = $this->bundlesSql($bundle_name, $prevnext_bundle);

    if ($search_criteria != 'nid') {
      $criteria_value = $this->database->queryRange("SELECT $search_criteria FROM {node_field_data} WHERE nid = :nid", 0, 1, [':nid' => $entity_id])
        ->fetchField();
      $next_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE (($search_criteria = :value AND nid > :nid) OR $search_criteria > :value) AND status = 1 $cond ORDER BY $search_criteria ASC,nid ASC", 0, 1, [
        ':value' => $criteria_value,
        ':nid' => $entity_id,
      ])->fetchField();
      $prev_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE (($search_criteria = :value AND nid < :nid) OR $search_criteria < :value) AND status = 1 $cond ORDER BY $search_criteria DESC,nid DESC", 0, 1, [
        ':value' => $criteria_value,
        ':nid' => $entity_id,
      ])->fetchField();
    }
    else {
      $next_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE nid > :nid AND status = 1 $cond ORDER BY nid ASC", 0, 1, [':nid' => $entity_id])
        ->fetchField();
      $prev_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE nid < :nid AND status = 1 $cond ORDER BY nid DESC", 0, 1, [':nid' => $entity_id])
        ->fetchField();
    }
    // Update the node-level data.
    $exists = (bool) $this->database->queryRange('SELECT 1 FROM {prev_next_node} WHERE nid = :nid', 0, 1, [':nid' => $entity_id])
      ->fetchField();
    if (!empty($exists)) {
      $this->database->update('prev_next_node')
        ->fields([
          'prev_nid' => ($prev_nid) ? $prev_nid : 0,
          'next_nid' => ($next_nid) ? $next_nid : 0,
          'changed' => REQUEST_TIME,
        ])
        ->condition('nid', $entity_id)
        ->execute();
    }
    else {
      $id = $this->database->insert('prev_next_node')
        ->fields([
          'prev_nid' => ($prev_nid) ? $prev_nid : 0,
          'next_nid' => ($next_nid) ? $next_nid : 0,
          'changed' => REQUEST_TIME,
          'nid' => $entity_id,
        ])
        ->execute();
    }

    // Update the other entities pointing to this entity.
    foreach (NodeType::loadMultiple() as $type => $name) {
      $prevnext_bundles = $this->prevnextHelper->getBundleNames();
      if (in_array($type, $prevnext_bundles)) {
        $prevnext_bundle = $this->prevnextHelper->loadBundle($type);
        $search_criteria = $prevnext_bundle->get('indexing_criteria');
        $cond = $this->bundlesSql($bundle_name, $prevnext_bundle);

        if ($search_criteria != 'nid') {
          $criteria_value = $this->database->queryRange("SELECT $search_criteria FROM {node_field_data} WHERE nid = :nid", 0, 1, [':nid' => $entity_id])
            ->fetchField();
          $prev_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE (($search_criteria = :value AND nid > :nid) OR $search_criteria > :value) AND status = 1 $cond ORDER BY $search_criteria ASC,nid ASC", 0, 1, [
            ':value' => $criteria_value,
            ':nid' => $entity_id,
          ])->fetchField();
          $next_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE (($search_criteria = :value AND nid < :nid) OR $search_criteria < :value) AND status = 1 $cond ORDER BY $search_criteria DESC,nid DESC", 0, 1, [
            ':value' => $criteria_value,
            ':nid' => $entity_id,
          ])->fetchField();
        }
        else {
          $prev_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE nid > :nid AND status = 1 $cond ORDER BY nid ASC", 0, 1, [':nid' => $entity_id])
            ->fetchField();
          $next_nid = $this->database->queryRange("SELECT nid FROM {node_field_data} WHERE nid < :nid AND status = 1 $cond ORDER BY nid DESC", 0, 1, [':nid' => $entity_id])
            ->fetchField();
        }
      }
      if ($next_nid) {
        $this->database->update('prev_next_node')
          ->fields([
            'next_nid' => $entity_id,
          ])
          ->condition('nid', $next_nid)
          ->execute();
      }
      if ($prev_nid) {
        $this->database->update('prev_next_node')
          ->fields([
            'prev_nid' => $entity_id,
          ])
          ->condition('nid', $prev_nid)
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update($entity_id, $bundle_name) {
    // Find out if any other entities point to this node and update them.
    $this->modifyPointingEntities($entity_id, $bundle_name);
    // Then update this one.
    $this->add($entity_id, $bundle_name);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($entity_id, $bundle_name) {
    // Delete the data for this node.
    $this->database->delete('prev_next_node')
      ->condition('nid', $entity_id)
      ->execute();

    // Find out if any other nodes point to this node and update them.
    $this->modifyPointingEntities($entity_id, $bundle_name);
  }

  /**
   * {@inheritdoc}
   */
  public function bundlesSql($bundle_name, $bundle) {
    $same_type = $bundle->get('same_type');
    if (!$same_type) {
      $types = $this->prevnextHelper->getBundleNames();
      $quoted_types = [];
      foreach ($this->prevnextHelper->getBundleNames() as $type) {
        $quoted_types[] = "'" . $type . "'";
      }
      $cond = '';
      if (count($types)) {
        $cond = 'AND type IN (' . implode(',', $quoted_types) . ')';
      }
    }
    else {
      $cond = "AND type = '" . $bundle_name . "'";
    }
    return $cond;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyPointingEntities($entity_id, $bundle_name) {
    // First for previous.
    $prev = $this->database->query("SELECT nid FROM {prev_next_node} WHERE prev_nid = :prev_nid", array(':prev_nid' => $entity_id))->fetchField();
    if ($prev) {
      $this->add($prev, $bundle_name);
    }

    // Then for next.
    $next = $this->database->query("SELECT nid FROM {prev_next_node} WHERE next_nid = :next_nid", array(':next_nid' => $entity_id))->fetchField();
    if ($next) {
      $this->add($next, $bundle_name);
    }
  }

}
