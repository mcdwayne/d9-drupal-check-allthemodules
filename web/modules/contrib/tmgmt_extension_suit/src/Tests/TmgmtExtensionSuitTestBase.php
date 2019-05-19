<?php

namespace Drupal\tmgmt_extension_suit\Tests;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Tests\TMGMTTestBase;

// Note we have to disable the SYMFONY_DEPRECATIONS_HELPER to ensure deprecation
// notices are not triggered.
// TODO: remove this and fix all the deprecations before Drupal 9.0.0.
putenv('SYMFONY_DEPRECATIONS_HELPER=disabled');

/**
 * Basic class for tmgmt extension module.
 */
abstract class TmgmtExtensionSuitTestBase extends TMGMTTestBase {

  /**
   * Logged in user.
   *
   * @var Drupal\user\UserInterface
   */
  private $userForTranslations;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'tmgmt',
    'tmgmt_demo',
    'tmgmt_extension_suit',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->userForTranslations = $this->loginAsAdmin([
      'edit any translatable_node content',
    ]);

    // Create queue table (it doesn't exist for simpletests somehow).
    $uploadQueue = new DatabaseQueue('tmgmt_extension_suit_upload', Database::getConnection());
    $database_schema = Drupal::database()->schema();

    try {
      if (!$database_schema->tableExists('queue')) {
        $schema_definition = $uploadQueue->schemaDefinition();
        $database_schema->createTable('queue', $schema_definition);
      }
    }
    catch (SchemaObjectExistsException $e) {
    }

    // Apply new column into tmgmt_job table.
    Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Check if checkbox is checked.
   *
   * @param $id
   * @return bool
   */
  protected function isFieldChecked($id) {
    $elements = $this->xpath('//input[@id=:id]', [':id' => $id]);

    return isset($elements[0]) && !empty($elements[0]['checked']);
  }
  /**
   * Requests nodes for translation.
   *
   * @param array $nids
   * @param $targetLanguage
   * @param $jobId
   */
  protected function requestTranslation(array $nids, $targetLanguage, $jobId) {
    // Request nodes for translation.
    $data = [];

    foreach ($nids as $nid) {
      $data["items[$nid]"] = "item[$nid]";
    }

    $this->drupalPostForm('admin/tmgmt/sources', $data, t('Request translation'));

    $data = [
      'label[0][value]' => 'Test job',
      'target_language' => $targetLanguage,
      'translator' => 'local',
    ];
    $this->drupalPostForm("admin/tmgmt/jobs/$jobId", $data, t('Submit to provider'));
  }

  /**
   * Requests nodes for translation in a batch.
   *
   * @param array $nids
   * @param array $targetLanguages
   * @param $provider
   * @param array $settings
   * @throws \Exception
   */
  protected function requestBulkTranslation(array $nids, array $targetLanguages, $provider = 'local', array $settings = []) {
    // Request nodes for translation.
    $data = [];

    foreach ($nids as $nid) {
      $data["items[$nid]"] = "item[$nid]";
    }

    $this->drupalPostForm('admin/tmgmt/sources', $data, t('Request translation in a batch'));

    if (!empty($targetLanguages)) {
      $new_data = [];

      foreach ($targetLanguages as $targetLanguage) {
        $new_data["target_language[$targetLanguage]"] = $targetLanguage;
      }

      $new_data['translator'] = $provider;

      foreach ($settings as $setting => $value) {
        $new_data[$setting] = $value;
      }

      $this->drupalPostForm(NULL, $new_data, t('Request translation'));
    }
  }

  /**
   * @return Drupal\user\UserInterface
   */
  protected function getUserForTranslations() {
    return $this->userForTranslations;
  }

  /**
   * Translates job items (marks as "needs review").
   *
   * @param array $jobItemIds
   */
  protected function translateJobItems(array $jobItemIds) {
    foreach ($jobItemIds as $jobItemId) {
      $jobItem = JobItem::load($jobItemId);
      $jobItem->setState(JobItem::STATE_REVIEW);
      $jobItem->save();
    }
  }

  /**
   * Returns job item hash by node id and job id.
   *
   * @param $nid
   * @param $jobId
   *
   * @return bool|mixed
   */
  protected function getNodeHash($nid, $jobId) {
    $hashQuery = Drupal::database()->select('tmgmt_job_item', 'tji');
    $hashQuery->join('tmgmt_job', 'tj', 'tj.tjid = tji.tjid');
    $hash = $hashQuery->condition('tj.tjid', $jobId)
      ->condition('tji.item_id', $nid)
      ->fields('tji', [
        'tjid',
        'tes_source_content_hash',
      ])
    ->execute()
    ->fetchAllKeyed();

    return $hash ? reset($hash) : FALSE;
  }

  /**
   * Returns amount of items in a given queue.
   *
   * @param string $queueName
   *
   * @return mixed
   */
  protected function getCountOfItemsInQueue($queueName) {
    return Drupal::database()->select('queue', 'q')
      ->condition('q.name', $queueName)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Checks if job was added to a queue.
   *
   * @param $queue
   * @param $jobId
   *
   * @return mixed
   */
  protected function isItemAddedToQueue($queue, $jobId) {
    return Drupal::database()->select('queue', 'q')
      ->condition('q.name', $queue)
      ->condition('q.data', "a:1:{s:2:\"id\";i:{$jobId};}")
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
