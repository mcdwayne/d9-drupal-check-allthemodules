<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use PDO;

/**
 * Class ExperienceStorage.
 *
 * @package Drupal\abtestui\Service
 */
class AbjsExperienceStorage {

  const BASE_TABLE_NAME = 'abjs_experience';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db;

  private $logger;

  private $state;

  /**
   *
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    StateInterface $state
  ) {
    $this->db = $database;
    $this->logger = $loggerChannelFactory->get('abtestui');
    $this->state = $state;
  }

  /**
   *
   */
  public function createOrLoadControl() {
    $controlExperienceID = $this->loadControl();

    // If no control exists, create it.
    if (FALSE === $controlExperienceID) {
      $controlExperienceID = $this->createControl();
    }

    // Store the eid in the site state so we don't have to search the DB.
    $this->state->set('abtestui', [
      'control_experience_eid' => $controlExperienceID,
    ]);

    return $controlExperienceID;
  }

  /**
   * @return string|int|bool
   */
  public function loadControl() {
    $abtestuiState = $this->state->get('abtestui');
    if (isset($abtestuiState['control_experience_eid'])) {
      return $abtestuiState['control_experience_eid'];
    }

    // Search for an existing 'Control'.
    $controlExperienceID = $this->db->select(AbjsExperienceStorage::BASE_TABLE_NAME)
      ->fields(AbjsExperienceStorage::BASE_TABLE_NAME, ['eid'])
      ->range(NULL, 1)
      ->condition('script', '')
      ->execute()
      ->fetchAssoc();

    $controlExperienceID = isset($controlExperienceID['eid']) ? $controlExperienceID['eid'] : FALSE;

    if (FALSE !== $controlExperienceID) {
      $this->logger->notice("Control experience with ID $controlExperienceID found.");
    }

    return $controlExperienceID;
  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function createControl() {
    // Create a 'Control' experience.
    // @todo: Re-add \Drupal::time() without breaking 8.2 core compatibility.
    $requestTime = REQUEST_TIME;
    $user = \Drupal::currentUser();

    $controlExperienceID = $this->db->insert(AbjsExperienceStorage::BASE_TABLE_NAME)
      ->fields([
        'name' => 'Base URL',
        'script' => '',
        'created' => $requestTime,
        'created_by' => $user->id(),
        'changed' => $requestTime,
        'changed_by' => $user->id(),
      ])
      ->execute();

    $this->logger->notice("Control experience with ID $controlExperienceID created.");

    return $controlExperienceID;
  }

  /**
   * Insert or update an experience.
   *
   * @param array $values
   *   An associative array of values.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function save(array $values) {
    // If there's no eid, insert.
    if (NULL === $values['eid']) {
      $result = $this->db->insert(AbjsExperienceStorage::BASE_TABLE_NAME)->fields([
        'name' => $values['name'],
        'script' => $values['script'],
        'created' => $values['created'],
        'created_by' => $values['created_by'],
        'changed' => $values['changed'],
        'changed_by' => $values['changed_by'],
      ])->execute();

      return $result;
    }

    $this->db->update(AbjsExperienceStorage::BASE_TABLE_NAME)->fields([
      'name' => $values['name'],
      'script' => $values['script'],
      'created' => $values['created'],
      'created_by' => $values['created_by'],
      'changed' => $values['changed'],
      'changed_by' => $values['changed_by'],
    ])->condition('eid', $values['eid'])
      ->execute();

    return $values['eid'];
  }

  /**
   * @param $tids
   *
   * @return int[]
   */
  public function loadMultipleByTids($tids) {
    $query = $this->db->select(AbjsTestStorage::EXPERIENCE_RELATION_TABLE, 'base_table');
    $query->condition('base_table.tid', $tids, 'in');
    $query->addField('base_table', 'eid');
    $result = $query->execute();

    return array_keys($result->fetchAllAssoc('eid', PDO::FETCH_ASSOC));
  }

  /**
   *
   */
  public function deleteMultiple(array $eids) {
    if (empty($eids)) {
      return;
    }

    $query = $this->db->delete(AbjsExperienceStorage::BASE_TABLE_NAME);
    $query->condition('eid', $eids, 'in');
    $query->execute();
  }

  /**
   * Remove experiences for the given tests.
   *
   * Does NOT remove the control experience.
   *
   * @param array $tids
   */
  public function deleteMultipleByTids(array $tids) {
    if (empty($tids)) {
      return;
    }

    $eids = $this->loadMultipleByTids($tids);
    $controlEid = $this->loadControl();

    // We don't want to delete the control experience.
    if (FALSE !== $controlEid && in_array($controlEid, $eids, FALSE)) {
      $eids = array_values(array_filter($eids, function ($value) use ($controlEid) {
        return (int) $value !== (int) $controlEid;
      }));
    }

    $this->deleteMultiple($eids);
  }

  /**
   *
   */
  public function delete($eid) {
    $query = $this->db->delete(AbjsExperienceStorage::BASE_TABLE_NAME);
    $query->condition('eid', $eid);
    $query->execute();
  }

}
