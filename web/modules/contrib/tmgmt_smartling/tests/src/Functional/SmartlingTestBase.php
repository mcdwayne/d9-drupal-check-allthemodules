<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\node\Entity\Node;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Smartling\Exceptions\SmartlingApiException;

// Note we have to disable the SYMFONY_DEPRECATIONS_HELPER to ensure deprecation
// notices are not triggered.
// TODO: remove this and fix all the deprecations before Drupal 9.0.0.
putenv('SYMFONY_DEPRECATIONS_HELPER=disabled');

/**
 * Basic tests for the Smartling translator.
 */
abstract class SmartlingTestBase extends TMGMTTestBase {

  /**
   * Name of file that contains settings for test Smartling project.
   *
   * @var string
   */
  const SETTINGS_FILE_NAME = 'tmgmt_smartling.simpletest.settings.php';

  /**
   * Smartling test project settings.
   *
   * @var array
   */
  protected $smartlingPluginProviderSettings = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'tmgmt',
    'tmgmt_demo',
    'tmgmt_smartling',
    'tmgmt_smartling_context_debug',
    'tmgmt_smartling_test',
    'tmgmt_smartling_log_settings',
    'dblog',
  ];

  /**
   * @var int
   */
  protected $testNodeId = 3;

  /**
   * @var string
   */
  protected $testNodeTitle;

  /**
   * @var string
   */
  protected $testNodeBody;

  /**
   * @var string
   */
  protected $targetLanguage = 'fr';

  /**
   * @var string
   */
  protected $sourceLanguage = 'en';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $settings_file_path = __DIR__ . '/../../../' . self::SETTINGS_FILE_NAME;

    // Include settings from external file.
    if (file_exists($settings_file_path) && empty($this->smartlingPluginProviderSettings)) {
      require_once $settings_file_path;

      // Fetch needed data.
      unset($settings['settings[export_format]']);
      $this->smartlingPluginProviderSettings = $settings;
      $test_node = Node::load($this->testNodeId);
      $this->testNodeTitle = $test_node->get('title')->value;
      $this->testNodeBody = trim(strip_tags($test_node->get('body')->value));
    }

    // Additional permission: access to "Recent log messages" page and
    // access for manual context sending.
    $this->loginAsAdmin([
      'access site reports',
      'send context smartling',
    ]);
  }

  /**
   * Invokes private/protected method.
   *
   * @param $object
   * @param $methodName
   * @param array $parameters
   *
   * @return mixed
   */
  protected function invokeMethod($object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }

  /**
   * Removes test file from Smartling dashboard.
   *
   * @param $fileName
   */
  protected function deleteTestFile($fileName) {
    try {
      $api_factory = Drupal::service('tmgmt_smartling.smartling_api_factory');
      $smartlingApi = $api_factory::create([
        'user_id' => $this->smartlingPluginProviderSettings['settings[user_id]'],
        'project_id' => $this->smartlingPluginProviderSettings['settings[project_id]'],
        'token_secret' => $this->smartlingPluginProviderSettings['settings[token_secret]'],
      ], 'file');
      $smartlingApi->deleteFile($fileName);
    }
    catch (SmartlingApiException $e) {
      // File not found.
    }
  }

  /**
   * Sets up Smartling provider settings and returns translator plugin.
   *
   * @param array $providerSettings
   *
   * @return \Drupal\tmgmt\TranslatorInterface
   */
  protected function setUpSmartlingProviderSettings(array $providerSettings) {
    $translator = $this->createTranslator([
      'plugin' => 'smartling',
      'auto_accept' => $providerSettings['auto_accept'],
      'settings' => [
        'project_id' => $providerSettings['settings[project_id]'],
        'user_id' => $providerSettings['settings[user_id]'],
        'token_secret' => $providerSettings['settings[token_secret]'],
        'contextUsername' => $providerSettings['settings[contextUsername]'],
        'context_silent_user_switching' => $providerSettings['settings[context_silent_user_switching]'],
        'retrieval_type' => $providerSettings['settings[retrieval_type]'],
        'auto_authorize_locales' => $providerSettings['settings[auto_authorize_locales]'],
        'callback_url_use' => $providerSettings['settings[callback_url_use]'],
        'callback_url_host' => $providerSettings['settings[callback_url_host]'],
        'scheme' => $providerSettings['settings[scheme]'],
        'custom_regexp_placeholder' => $providerSettings['settings[custom_regexp_placeholder]'],
        'enable_smartling_logging' => $providerSettings['settings[enable_smartling_logging]'],
        'enable_notifications' => $providerSettings['settings[enable_notifications]'],
      ],
    ]);

    return $translator;
  }

  /**
   * Requests translation for a given node.
   *
   * @param $nid
   * @param $language
   * @param $translator
   *
   * @return \Drupal\tmgmt\JobInterface
   */
  protected function requestTranslationForNode($nid, $language, $translator) {
    if (!is_array($nid)) {
      $nid = [$nid];
    }

    $api_wrapper = $translator->getPlugin()->getApiWrapper($translator->getSettings());
    $job_id = $api_wrapper->createJob('Drupal TMGMT connector test ' . mt_rand(), '');
    $batch_uid = $api_wrapper->createBatch($job_id, FALSE);

    $job = $this->createJob($this->sourceLanguage, $language, 1, [
      'label' => 'Job for ' . implode(', ', $nid),
      'job_type' => Job::TYPE_NORMAL,
    ]);
    $job->set('settings', [
      'batch_uid' => $batch_uid,
      'batch_execute_on_job' => $job->id(),
    ]);
    $job->translator = $translator;

    foreach ($nid as $item) {
      $job->addItem('content', 'node', $item);
    }

    $job->setState(JobInterface::STATE_ACTIVE);
    $job->requestTranslation();

    $api_wrapper->cancelJob($job_id);

    return $job;
  }

  /**
   * Checks if generated file exists and correct.
   *
   * @param $fileName
   * @param $nodeTitle
   * @param string $format
   */
  protected function checkGeneratedFile($fileName, $nodeTitle, $format = 'xml') {
    $file_path = \Drupal::getContainer()->get('file_system')->realpath(file_default_scheme() . "://tmgmt_sources/$fileName");
    $content = file_get_contents($file_path);

    $this->assertTrue(strpos($content, $nodeTitle) !== FALSE, 'Title is in file');

    $no_directives = $format != 'xml';

    $this->assertTrue(strpos($content, '<limit>255</limit>') !== $no_directives);
    $this->assertTrue(strpos($content, '<span sl-variant="node-3-title][0][value" class="atom" id="bMV1bdGl0bGVdWzBdW3ZhbHVl">') !== $no_directives);
    $this->assertTrue(strpos($content, '<limit>NONE</limit>') !== $no_directives);
    $this->assertTrue(strpos($content, '<div sl-variant="node-3-body][0][value" class="atom" id="bMV1bYm9keV1bMF1bdmFsdWU">') !== $no_directives);
  }

  /**
   * Checks if download was successful.
   *
   * @param $jobId
   * @param $fileName
   * @throws \Exception
   */
  protected function downloadAndCheckTranslatedFile($jobId, $fileName) {
    $this->drupalPostForm("admin/tmgmt/jobs/$jobId", [], t('Download'));
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(200);
    // TODO: don't know why assertLink and assertRaw doesn't work with quoted
    // strings.
    $this->assertRaw('Translation for');
    $this->assertRaw($fileName);
    $this->assertRaw('was successfully downloaded and imported.');
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
   * Returns queue items data.
   *
   * @param $queue_name
   * @return array
   */
  protected function fetchQueueItemsData($queue_name) {
    $result = [];

    $data_items = \Drupal::database()->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('q.name', $queue_name)
      ->execute()
      ->fetchCol();

    foreach ($data_items as $data_item) {
      $result[] = unserialize($data_item);
    }

    return $result;
  }

  /**
   * Processes cron queue.
   * @param $name
   */
  protected function processQueue($name) {
    $queue_factory = Drupal::service('queue');
    $queue_manager = Drupal::service('plugin.manager.queue_worker');

    // Grab the defined cron queues.
    foreach ($queue_manager->getDefinitions() as $queue_name => $info) {
      if ($queue_name != $name) {
        continue;
      }

      if (isset($info['cron'])) {
        // Make sure every queue exists. There is no harm in trying to recreate
        // an existing queue.
        $queue_factory->get($queue_name)->createQueue();

        $queue_worker = $queue_manager->createInstance($queue_name);
        $end = time() + (isset($info['cron']['time']) ? $info['cron']['time'] : 15);
        $queue = $queue_factory->get($queue_name);
        $lease_time = isset($info['cron']['time']) ?: NULL;
        while (time() < $end && ($item = $queue->claimItem($lease_time))) {
          try {
            $queue_worker->processItem($item->data);
            $queue->deleteItem($item);
          }
          catch (RequeueException $e) {
            // The worker requested the task be immediately requeued.
            $queue->releaseItem($item);
          }
          catch (SuspendQueueException $e) {
            // If the worker indicates there is a problem with the whole queue,
            // release the item and skip to the next queue.
            $queue->releaseItem($item);

            watchdog_exception('cron', $e);

            // Skip to the next queue.
            continue 2;
          }
          catch (\Exception $e) {
            // In case of any other kind of exception, log it and leave the item
            // in the queue to be processed again later.
            watchdog_exception('cron', $e);
          }
        }
      }
    }
  }

  /**
   * Same as UiHelperTrait::submitForm() but is able to fill in hidden fields.
   */
  protected function submitForm(array $edit, $submit, $form_html_id = NULL) {
    $assert_session = $this->assertSession();

    // Get the form.
    if (isset($form_html_id)) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_html_id']");
      $submit_button = $assert_session->buttonExists($submit, $form);
      $action = $form->getAttribute('action');
    }
    else {
      $submit_button = $assert_session->buttonExists($submit);
      $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
      $action = $form->getAttribute('action');
    }

    // Edit the form values.
    foreach ($edit as $name => $value) {
      // If field is not found then probably it's a hidden field.
      try {
        $field = $assert_session->fieldExists($name, $form);
      } catch (ElementNotFoundException $e) {
        $field = $assert_session->hiddenFieldExists($name, $form);
      }

      // Provide support for the values '1' and '0' for checkboxes instead of
      // TRUE and FALSE.
      // @todo Get rid of supporting 1/0 by converting all tests cases using
      // this to boolean values.
      $field_type = $field->getAttribute('type');
      if ($field_type === 'checkbox') {
        $value = (bool) $value;
      }

      $field->setValue($value);
    }

    // Submit form.
    $this->prepareRequest();
    $submit_button->press();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Check if there are any meta refresh redirects (like Batch API pages).
    if ($this->checkForMetaRefresh()) {
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    // Log only for JavascriptTestBase tests because for Goutte we log with
    // ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !($this->getSession()->getDriver() instanceof GoutteDriver)) {
      $out = $this->getSession()->getPage()->getContent();
      $html_output = 'POST request to: ' . $action .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

  }
}
