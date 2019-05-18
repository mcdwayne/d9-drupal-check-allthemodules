<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;
use PDO;

/**
 * Class ConditionStorage.
 *
 * @package Drupal\abtestui\Service
 */
class AbjsConditionStorage {

  const BASE_TABLE_NAME = 'abjs_condition';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db;

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->db = $database;
  }

  /**
   *
   */
  public function save(array $values) {
    // If there's no cid, insert.
    if (NULL === $values['cid']) {
      $result = $this->db->insert(AbjsConditionStorage::BASE_TABLE_NAME)->fields([
        'name' => $values['name'],
        'script' => $values['script'],
        'created' => $values['created'],
        'created_by' => $values['created_by'],
        'changed' => $values['changed'],
        'changed_by' => $values['changed_by'],
      ])->execute();

      return $result;
    }

    $this->db->update(AbjsConditionStorage::BASE_TABLE_NAME)->fields([
      'name' => $values['name'],
      'script' => $values['script'],
      'changed' => $values['changed'],
      'changed_by' => $values['changed_by'],
    ])->condition('cid', $values['cid'])
      ->execute();

    return $values['cid'];
  }

  /**
   *
   */
  public function loadMultipleByTids($tids) {
    $query = $this->db->select(AbjsTestStorage::CONDITION_RELATION_TABLE, 'base_table');
    $query->condition('base_table.tid', $tids, 'in');
    $query->addField('base_table', 'cid');
    $result = $query->execute();

    return array_keys($result->fetchAllAssoc('cid', PDO::FETCH_ASSOC));
  }

  /**
   *
   */
  public function deleteMultiple(array $cids) {
    if (empty($cids)) {
      return;
    }

    $query = $this->db->delete(AbjsConditionStorage::BASE_TABLE_NAME);
    $query->condition('cid', $cids, 'in');
    $query->execute();
  }

  /**
   *
   */
  public function deleteMultipleByTids(array $tids) {
    if (empty($tids)) {
      return;
    }

    $this->deleteMultiple($this->loadMultipleByTids($tids));
  }

}
