<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\node\Entity\Node;
use Smartling\Jobs\JobStatus;

/**
 * Logging tests.
 *
 * @group tmgmt_smartling
 */
class LoggingTest extends SmartlingTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

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

    $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
      'settings[project_id]' => $this->smartlingPluginProviderSettings['settings[project_id]'],
      'settings[user_id]' => $this->smartlingPluginProviderSettings['settings[user_id]'],
      'settings[token_secret]' => $this->smartlingPluginProviderSettings['settings[token_secret]'],
      'settings[contextUsername]' => $this->smartlingPluginProviderSettings['settings[contextUsername]'],
      'settings[context_silent_user_switching]' => $this->smartlingPluginProviderSettings['settings[context_silent_user_switching]'],
      'settings[retrieval_type]' => $this->smartlingPluginProviderSettings['settings[retrieval_type]'],
      'settings[auto_authorize_locales]' => $this->smartlingPluginProviderSettings['settings[auto_authorize_locales]'],
      'settings[callback_url_use]' => $this->smartlingPluginProviderSettings['settings[callback_url_use]'],
      'settings[callback_url_host]' => $this->smartlingPluginProviderSettings['settings[callback_url_host]'],
      'settings[scheme]' => $this->smartlingPluginProviderSettings['settings[scheme]'],
      'settings[custom_regexp_placeholder]' => $this->smartlingPluginProviderSettings['settings[custom_regexp_placeholder]'],
    ], t('Save'));
  }

  /**
   * Test request translation logging.
   */
  public function testRequestTranslationEventLogging() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->drupalGet('/admin/reports/dblog');

      // File triggered/queued.
      $this->assertRaw('File upload triggered (request translation). Job id: 1, file name: JobID1_en_de.xml.');
      $this->assertNoRaw('File upload queued (track entity changes). Job id: 1, file name: JobID1_en_de.xml.');

      // File uploaded.
      $this->assertRaw('File uploaded. Job id: 1, file name: JobID1_en_de.xml.');
      $this->assertNoRaw('File uploaded. Job id: 2, file name: JobID2_en_fr.xml.');

      $api_wrapper = Drupal::service('tmgmt_smartling.smartling_api_wrapper');
      $api_wrapper->setSettings([
        'user_id' => $this->smartlingPluginProviderSettings['settings[user_id]'],
        'project_id' => $this->smartlingPluginProviderSettings['settings[project_id]'],
        'token_secret' => $this->smartlingPluginProviderSettings['settings[token_secret]'],
      ]);
      $jobs = $api_wrapper->listJobs(NULL, [
        JobStatus::AWAITING_AUTHORIZATION,
        JobStatus::IN_PROGRESS,
      ]);

      if (!empty($jobs['items'])) {
        foreach ($jobs['items'] as $job) {
          if (strpos($job['jobName'], 'Drupal TMGMT connector test') !== FALSE) {
            $api_wrapper->cancelJob($job['translationJobUid']);
          }
        }
      }
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test request translation track entity changes logging.
   */
  public function testTrackEntityChangesEventLogging() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->userForTranslations = $this->loginAsAdmin([
        'edit any translatable_node content',
        'access site reports',
      ]);

      // Create a job. It is needed for test.
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $this->requestTranslationForNode($this->testNodeId, 'de', $translator);

      // Clean log messages after job submission.
      $this->drupalPostForm('/admin/reports/dblog/confirm', [], t('Confirm'));

      $node = Node::load($this->testNodeId);
      $node->setTitle('New title');
      $node->save();

      $this->drupalGet('/admin/reports/dblog');

      // File triggered/queued.
      $this->assertNoRaw('File upload triggered (request translation). Job id: 1, file name: JobID1_en_de.xml.');
      $this->assertRaw('File upload queued (track entity changes). Job id: 1, file name: JobID1_en_de.xml.');

      // File uploaded.
      $this->assertNoRaw('File uploaded. Job id: 1, file name: JobID1_en_de.xml.');
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
