<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class TestStorage.
 *
 * @package Drupal\abtestui\Service
 */
class TestStorage {

  const BASE_TABLE_NAME = 'abtestui_test';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db;

  private $logger;

  private $variationStorage;

  private $abjsTestStorage;

  private $abjsConditionStorage;

  private $abjsExperienceStorage;

  /**
   *
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    VariationStorage $variationStorage,
    AbjsTestStorage $abjsTestStorage,
    AbjsConditionStorage $abjsConditionStorage,
    AbjsExperienceStorage $abjsExperienceStorage
  ) {
    $this->db = $database;
    $this->logger = $loggerChannelFactory->get('abtestui');
    $this->variationStorage = $variationStorage;
    $this->abjsTestStorage = $abjsTestStorage;
    $this->abjsConditionStorage = $abjsConditionStorage;
    $this->abjsExperienceStorage = $abjsExperienceStorage;
  }

  /**
   *
   */
  public function load($tid) {
    if (NULL === $tid) {
      return FALSE;
    }

    $test = $this->fetchTest($tid);

    if (FALSE === $test) {
      return FALSE;
    }

    $conditions = $this->fetchConditions($tid);
    $experiences = $this->fetchExperiences($tid);

    $base_variation = $experiences[$test['abjs_teid']];
    $base_condition = $conditions[$test['abjs_tcid']];

    $test['base_odd'] = $base_variation->fraction * 100;
    $test['eid'] = $base_variation->eid;
    $test['cid'] = $base_condition->cid;
    unset($experiences[$test['abjs_teid']]);

    $test['variations'] = [];

    /** @var array $experience */
    foreach ($experiences as $experience) {
      $test['variations'][] = [
        'name' => $experience->name,
        'eid' => $experience->eid,
        'url' => $experience->url,
        'odd' => $experience->fraction * 100,
        'vid' => $experience->vid,
        'teid' => $experience->teid,
      ];
    }

    return $test;
  }

  /**
   *
   */
  private function fetchTest($tid) {
    $query = $this->db->select(TestStorage::BASE_TABLE_NAME, 'base_table');
    $query->addJoin('left', 'abjs_test', 'test', 'base_table.tid = test.tid');
    $query->condition('base_table.tid', $tid);
    $query->fields('base_table');
    $query->fields('test');
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   *
   */
  private function fetchConditions($tid) {
    $query = $this->db->select('abjs_condition', 'cond');
    $query->addJoin('left', 'abjs_test_condition', 'test_cond', 'cond.cid = test_cond.cid');
    $query->condition('test_cond.tid', $tid);
    $query->fields('cond');
    $query->fields('test_cond', ['tcid']);
    $cResult = $query->execute();
    return $cResult->fetchAllAssoc('tcid');
  }

  /**
   *
   */
  private function fetchExperiences($tid) {
    $query = $this->db->select('abjs_experience', 'exp');
    $query->addJoin('left', 'abjs_test_experience', 'test_exp', 'exp.eid = test_exp.eid');
    $query->addJoin('left', 'abtestui_variation', 'var', 'test_exp.teid = var.abjs_teid');
    $query->condition('test_exp.tid', $tid);
    $query->fields('exp');
    $query->fields('test_exp', ['teid', 'fraction']);
    $query->fields('var', ['abjs_teid', 'url', 'vid']);
    $result = $query->execute();
    return $result->fetchAllAssoc('teid');
  }

  /**
   *
   */
  public function save($values) {
    $this->db->merge(static::BASE_TABLE_NAME)
      ->key('tid', $values['tid'])
      ->fields([
        'tid' => $values['tid'],
        'base_url' => $values['base_url'],
        'analytics_url' => $values['analytics_url'],
        'abjs_tcid' => $values['abjs_tcid'],
        'abjs_teid' => $values['abjs_teid'],
      ])
      ->execute();

    return $values['tid'];
  }

  /**
   *
   */
  public function loadMultiple(array $tids = []) {
    if (empty($tids)) {
      $query = $this->db->select(static::BASE_TABLE_NAME, 'base_table');
      $query->addField('base_table', 'tid');
      $result = $query->execute();
      $tids = $result->fetchCol();
    }

    $tests = [];
    foreach ($tids as $tid) {
      $test = $this->load($tid);
      if (FALSE === $test) {
        $this->logger->error('Test with ID @tid could not be loaded.', [
          '@tid' => $tid,
        ]);
        continue;
      }
      $tests[$tid] = $test;
    }

    return $tests;
  }

  /**
   * Delete a test and every dependent row.
   *
   * Removes from the following tables:
   *   abjs_test
   *   abjs_condition
   *   abjs_experience
   *   abjs_test_condition
   *   abjs_test_experience
   *   abtestui_test
   *   abtestui_variation.
   *
   * @param string|int $tid
   *   The test id.
   */
  public function delete($tid) {
    $this->deleteMultiple([$tid]);
  }

  /**
   * Remove multiple tests and their dependent rows.
   *
   * Removes from the following tables:
   *   abjs_test
   *   abjs_condition
   *   abjs_experience
   *   abjs_test_condition
   *   abjs_test_experience
   *   abtestui_test
   *   abtestui_variation.
   *
   * @param int[]|string[] $tids
   *   An array of test ids.
   */
  public function deleteMultiple(array $tids) {
    if (empty($tids)) {
      return;
    }

    // @todo: add storage deleteMultipleByTids
    // Remove abtestui_varitaion, abtestui_test, abjs_test rows.
    $query = $this->db->delete(static::BASE_TABLE_NAME);
    $query->condition('tid', $tids, 'in');
    $query->execute();

    $this->abjsTestStorage->deleteMultiple($tids);

    // @todo: add storage deleteMultipleByTids
    $query = $this->db->delete(VariationStorage::BASE_TABLE_NAME);
    $query->condition('tid', $tids, 'in');
    $query->execute();

    // Delete the experiences and conditions.
    $this->abjsExperienceStorage->deleteMultipleByTids($tids);
    $this->abjsConditionStorage->deleteMultipleByTids($tids);

    // @todo: add storage deleteMultipleByTids
    // Remove relations.
    $query = $this->db->delete(AbjsTestStorage::EXPERIENCE_RELATION_TABLE);
    $query->condition('tid', $tids, 'in');
    $query->execute();

    // @todo: add storage deleteMultipleByTids
    $query = $this->db->delete(AbjsTestStorage::CONDITION_RELATION_TABLE);
    $query->condition('tid', $tids, 'in');
    $query->execute();
  }

}
