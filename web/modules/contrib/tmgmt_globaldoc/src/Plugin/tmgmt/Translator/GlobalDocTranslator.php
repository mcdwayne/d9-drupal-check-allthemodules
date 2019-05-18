<?php

namespace Drupal\tmgmt_globaldoc\Plugin\tmgmt\Translator;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_file\Format\FormatManager;
use Drupal\tmgmt_globaldoc\Service\echoCustom;
use Drupal\tmgmt_globaldoc\Service\getSourceTask;
use Drupal\tmgmt_globaldoc\Service\getTaskState;
use Drupal\tmgmt_globaldoc\Service\LangXpertService;
use Drupal\tmgmt_globaldoc\Service\setTaskState;
use Drupal\tmgmt_globaldoc\Service\submitSourceTask;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GlobalDoc translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "globaldoc",
 *   label = @Translation("GlobalDoc"),
 *   description = @Translation("GlobalDoc Translator service."),
 *   ui = "Drupal\tmgmt_globaldoc\GlobalDocTranslatorUi",
 *   logo = "icons/globaldoc.png",
 * )
 */
class GlobalDocTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Task status for an accepted source task.
   */
  const STATUS_SOURCE_ACCEPTED = 'SourceAccepted';

  /**
   * Task status for a completed source task.
   */
  const STATUS_SOURCE_COMPLETED = 'SourceCompleted';

  /**
   * Task status for a completed source task.
   */
  const STATUS_SOURCE_ACKNOWLEDGED = 'SourceAcknowledged';

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * List of LangXpert service instances, keyed by translator ID.
   *
   * @var \Drupal\tmgmt_globaldoc\Service\LangXpertService[]
   */
  protected $langXpertList;

  /**
   * The TMGMT file format manager.
   *
   * @var \Drupal\tmgmt_file\Format\FormatManager
   */
  protected $FileFormatManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a LocalActionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param \Drupal\tmgmt_file\Format\FormatManager $file_format_manager
   *   The TMGMT file format service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, FormatManager $file_format_manager, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->FileFormatManager = $file_format_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('plugin.manager.tmgmt_file.format'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = parent::defaultSettings();
    // Enable CDATA for content encoding in File translator.
    //$defaults['xliff_processing'] = TRUE;
    $defaults['xliff_cdata'] = TRUE;
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $translator = $job->getTranslator();

    $task_id = $this->buildTaskId($job);
    $task_state_result = $this->getTaskState($translator, $task_id);

    if (!empty($task_state_result->return)) {
      throw new TMGMTException('Task ID ' . $task_id . ' exists already');
    }

    $zip_filename = $this->buildZipFile($job, $task_id);

    try {
      $zip_data = file_get_contents($zip_filename);
      unlink($zip_filename);
      $source_task = new submitSourceTask($translator->getSetting('business_unit'), '', $task_id, $translator->getSetting('requester_id'), $job->getRemoteTargetLanguage(), $zip_data);

      if ($response = $this->getLangXpert($translator)->submitSourceTask($source_task)) {
        if ($response->return == static::STATUS_SOURCE_ACCEPTED) {
          $job->set('reference', $task_id);
          $job->submitted('Task ID @task_id successfully submitted.', ['@task_id' => $task_id]);
        }
        else {
          $job->rejected('Failed to submit task, returned status @status.', ['@status' => $response->return]);
        }
      }

    }
    catch (\Exception $e) {
      // @todo Workaround, currently successfull submissions result in a timeout
      //   after 60s. Blindly assume that this means it was successful.
      if ($e->getMessage() == 'Error Fetching http headers') {
        $job->set('reference', $task_id);
        $job->submitted('Task ID @task_id successfully submitted (Timeout).', ['@task_id' => $task_id]);
      }
      else {
        $job->rejected('Task has been rejected with following error: @error.', ['@error' => $e->getMessage()], 'error');
      }
    }
  }

  /**
   * Build the task id to have the format required by GlobalDoc.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The tmgmt job.
   *
   * @return string
   *   The task id with the format of GlobalDoc.
   */
  protected function buildTaskId(JobInterface $job) {
    return $job->id();
  }

  /**
   * Build the zip file that contains the xliff job string.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The tmgmt job.
   * @param string $task_id
   *   The task ID.
   *
   * @return string
   *   The task id with the format of GlobalDoc.
   */
  protected function buildZipFile($job, $task_id) {
    /** @var \Drupal\tmgmt_file\Format\FormatInterface $xliff_converter */
    $zip = new \ZipArchive();
    $xliff_converter = $this->FileFormatManager->createInstance('xlf');
    $xliff = $xliff_converter->export($job);
    $zip_filename = \Drupal::service('file_system')->realpath("temporary://{$task_id}.zip");
    $xliff_filename = "{$task_id}.xlf";
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
      throw new TMGMTException('Failed to create ZIP file for submitting source task.');
    }
    if (!$zip->addFromString($xliff_filename, $xliff)) {
      throw new TMGMTException('Failed to create ZIP file for submitting source task.');
    }
    $zip->close();
    return $zip_filename;
  }

  /**
   * Loads and returns supported remote languages.
   *
   * Reads from langxpert-lang-codemap.csv in the module folder.
   *
   * @return array
   *   A list of languages, keyed by the standard language code (e.g. en-US).
   *   Each language is an array with the keys label and file_langcode.
   */
  protected function loadRemoteLanguageList() {

    $module_path = \Drupal::service('module_handler')->getModule('tmgmt_globaldoc')->getPath();
    $filepath = $module_path . '/langxpert-lang-codemap.csv';

    // Use a cache that depends on the file.
    $file_cache = FileCacheFactory::get('tmgmt_globaldoc_language_list');
    if ($remote_languages = $file_cache->get($filepath)) {
      return $remote_languages;
    }

    $remote_languages = [];
    $handle = fopen($filepath, 'r');
    if (!$handle) {
      throw new TMGMTException('Failed to read langxpert-lang-codemap.csv.');
    }
    while (($data = fgetcsv($handle)) !== FALSE) {
      $remote_languages[$data[2]] = [
        'label' => $data[1],
        'file_langcode' => $data[0],
      ];
    }
    fclose($handle);

    // Sort the languages by label.
    uasort($remote_languages, function ($a, $b) {
      return strnatcmp($a['label'], $b['label']);
    });

    $file_cache->set($filepath, $remote_languages);
    return $remote_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $language_labels = [];
    foreach ($this->loadRemoteLanguageList() as $langcode => $info) {
      $language_labels[$langcode] = $info['label'];
    }
    return $language_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    // API supports only en-US/USEN as source language.
    if ($source_language == 'en-US') {
      $languages = $translator->getSupportedRemoteLanguages();
      unset($languages['en-US']);
      return $languages;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    $language_pairs = [];
    foreach ($this->getSupportedTargetLanguages($translator, 'en-US') as $target_language) {
      $language_pairs[] = [
        'source_language' => 'en-US',
        'target_language' => $target_language,
      ];
    }
    return $language_pairs;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return [
      'en' => 'en-US',
      'de' => 'de-DE',
      'fr' => 'fr-FR',
      'it' => 'it-IT',
    ];
  }

  /**
   * Returns the TaskState information.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator.
   *
   * @param string $task_id
   *   The task ID, stored as the external reference on existing jobs.
   *
   * @return \Drupal\tmgmt_globaldoc\Service\getTaskStateResponse
   *   The task state response object.
   */
  public function getTaskState(TranslatorInterface $translator, $task_id) {
    $get_task_state = new getTaskState($translator->getSetting('business_unit'), $translator->getSetting('requester_id'), $task_id);
    return $this->getLangXpert($translator)->getTaskState($get_task_state);
  }

  /**
   * Fetch translations.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job entity.
   */
  public function fetchTranslation(JobInterface $job) {

    $translator = $job->getTranslator();

    $task_id = $job->getReference();

    $get_source_task = new getSourceTask($translator->getSetting('business_unit'), $translator->getSetting('requester_id'), $task_id);
    $response = $this->getLangXpert($translator)->getSourceTask($get_source_task);

    if ($response->return->status != static::STATUS_SOURCE_COMPLETED) {
      $job->addMessage('Task is not ready for import, status: @status.', ['@status' => $response->return->status], 'error');
      return;
    }

    $zip_filename = \Drupal::service('file_system')->realpath("temporary://{$task_id}.zip");
    file_put_contents($zip_filename, $response->return->fileByte);

    $archive = new \ZipArchive();
    $sucess_open = $archive->open($zip_filename);
    if ($sucess_open !== TRUE) {
      $job->addMessage('Failed to read response, error code @code.', ['@code' => $sucess_open], 'error');
      return;
    }

    $xliff = $archive->getFromIndex(0);
    if (!$xliff) {
      $job->addMessage('Failed to read response, archive empty.', [], 'error');
      return;
    }

    /** @var \Drupal\tmgmt_file\Format\FormatInterface $xliff_converter */
    $xliff_converter = $this->FileFormatManager->createInstance('xlf');

    $validated_job = $xliff_converter->validateImport($xliff, FALSE);
    if (!$validated_job || $validated_job->id() != $job->id()) {
      $job->addMessage('Response data is not valid.', [], 'error');
      return;
    }

    $data = $xliff_converter->import($xliff, FALSE);

    $job->addTranslatedData($data);

    $task_state = new setTaskState($translator->getSetting('business_unit'), $task_id, static::STATUS_SOURCE_ACKNOWLEDGED, 'Translated File Received successfully');
    $this->getLangXpert($translator)->setTaskState($task_state);

    $job->addMessage('Translation fetched.');
  }

  /**
   * Returns a LangXpert service instance.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator entity.
   *
   * @return \Drupal\tmgmt_globaldoc\Service\LangXpertService
   *   The SOAP service.
   */
  protected function getLangXpert(TranslatorInterface $translator) {
    if (!isset($this->langXpertList[$translator->id()])) {
      if (!$translator->getSetting('security_token')) {
        throw new TMGMTException('Required security token missing');
      }
      if (!$translator->getSetting('wsdl')) {
        throw new TMGMTException('Required WSDL URl missing');
      }
      $this->langXpertList[$translator->id()] = new LangXpertService($translator->getSetting('security_token'), $translator->getSetting('wsdl'));
    }
    return $this->langXpertList[$translator->id()];
  }

}
