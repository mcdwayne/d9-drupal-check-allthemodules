<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;

/**
 * Class TestStorage.
 *
 * @package Drupal\abtestui\Service
 */
class AbjsTestStorage {

  const BASE_TABLE_NAME = 'abjs_test';
  const EXPERIENCE_RELATION_TABLE = 'abjs_test_experience';
  const CONDITION_RELATION_TABLE = 'abjs_test_condition';

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
    // If there's no tid, insert.
    if (NULL === $values['tid']) {
      $result = $this->db->insert(AbjsTestStorage::BASE_TABLE_NAME)->fields([
        'name' => $values['name'],
        'active' => $values['active'],
        'created' => $values['created'],
        'created_by' => $values['created_by'],
        'changed' => $values['changed'],
        'changed_by' => $values['changed_by'],
      ])->execute();

      return $result;
    }

    $this->db->update(AbjsTestStorage::BASE_TABLE_NAME)->fields([
      'name' => $values['name'],
      'active' => $values['active'],
      'changed' => $values['changed'],
      'changed_by' => $values['changed_by'],
    ])->condition('tid', $values['tid'])
      ->execute();

    return $values['tid'];
  }

  /**
   * @param $values
   */
  public function addExperienceRelation($values) {
    // If there's no teid, insert.
    if (NULL === $values['teid']) {
      $result = $this->db->insert(AbjsTestStorage::EXPERIENCE_RELATION_TABLE)->fields([
        'eid' => $values['eid'],
        'tid' => $values['tid'],
        'fraction' => $values['fraction'],
      ])->execute();

      return $result;
    }

    $this->db->update(AbjsTestStorage::EXPERIENCE_RELATION_TABLE)->fields([
      'eid' => $values['eid'],
      'tid' => $values['tid'],
      'fraction' => $values['fraction'],
    ])->condition('teid', $values['teid'])
      ->execute();

    return $values['teid'];
  }

  /**
   * @param $values
   *
   * @todo: Move to service.
   */
  public function addConditionRelation($values) {
    // If there's no tid, insert.
    if (NULL === $values['tcid']) {
      $result = $this->db->insert(AbjsTestStorage::CONDITION_RELATION_TABLE)->fields([
        'cid' => $values['cid'],
        'tid' => $values['tid'],
      ])->execute();

      return $result;
    }

    $this->db->update(AbjsTestStorage::CONDITION_RELATION_TABLE)->fields([
      'cid' => $values['cid'],
      'tid' => $values['tid'],
    ])->condition('tcid', $values['tcid'])
      ->execute();

    return $values['tcid'];
  }

  /**
   *
   */
  public function delete($tid) {
    $this->deleteMultiple([$tid]);
  }

  /**
   *
   */
  public function deleteMultiple(array $tids) {
    if (empty($tids)) {
      return;
    }

    $query = $this->db->delete(AbjsTestStorage::BASE_TABLE_NAME);
    $query->condition('tid', $tids, 'in');
    $query->execute();
  }

  /**
   *
   */
  public function deleteExperienceRelation($teid) {
    $query = $this->db->delete(AbjsTestStorage::EXPERIENCE_RELATION_TABLE);
    $query->condition('teid', $teid);
    $query->execute();
  }

}
