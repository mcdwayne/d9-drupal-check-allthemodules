<?php

/**
 * @file
 * Contains \Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator.
 */

namespace Drupal\tmgmt_smartling\Plugin\tmgmt\Translator;

use Drupal;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_extension_suit\ExtendedTranslatorPluginInterface;
use Drupal\tmgmt_file\Format\FormatManager;
use Drupal\tmgmt_smartling\Smartling\ConnectorInfo;
use Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper;
use Drupal\tmgmt_smartling\Smartling\Submission\TranslationRequestManager;
use Exception;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Smartling\AuditLog\Params\CreateRecordParameters;
use Smartling\BaseApiAbstract;
use Smartling\File\Params\UploadFileParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use \Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\tmgmt_smartling\Event\RequestTranslationEvent;

/**
 * Smartling translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "smartling",
 *   label = @Translation("Smartling translator"),
 *   description = @Translation("Smartling Translator service."),
 *   ui = "Drupal\tmgmt_smartling\SmartlingTranslatorUi"
 * )
 */
class SmartlingTranslator extends TranslatorPluginBase implements
  ExtendedTranslatorPluginInterface,
  ContainerFactoryPluginInterface,
  ContinuousTranslatorInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * @var \Drupal\tmgmt_file\Format\FormatManager
   */
  protected $formatPluginsManager;

  /**
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $fileUsage;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var SmartlingApiWrapper
   */
  private $smartlingApiWrapper;

  /**
   * @var TranslationRequestManager
   */
  private $translationRequestManager;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  protected $currentUser;

  /**
   * Constructs a LocalActionBase object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param \Drupal\tmgmt_file\Format\FormatManager $format_plugin_manager
   * @param \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper $api_wrapper
   * @param \Drupal\tmgmt_smartling\Smartling\Submission\TranslationRequestManager $translation_request_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    ClientInterface $client,
    FormatManager $format_plugin_manager,
    DatabaseFileUsageBackend $file_usage,
    EventDispatcherInterface $event_dispatcher,
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    LoggerInterface $logger,
    SmartlingApiWrapper $api_wrapper,
    TranslationRequestManager $translation_request_manager,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->formatPluginsManager = $format_plugin_manager;
    $this->fileUsage = $file_usage;
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger;
    $this->smartlingApiWrapper = $api_wrapper;
    $this->translationRequestManager = $translation_request_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $container->get('plugin.manager.tmgmt_file.format'),
      $container->get('file.usage'),
      $container->get('event_dispatcher'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.smartling'),
      $container->get('tmgmt_smartling.smartling_api_wrapper'),
      $container->get('tmgmt_smartling.translation_request_manager'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('user_id') &&
      $translator->getSetting('token_secret') &&
      $translator->getSetting('project_id')
    ) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
     ]));
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    // Anything can be exported.
    return TranslatableResult::yes();
  }

  /**
   * Returns callback url.
   *
   * Host value can be overridden by value defined in translator settings.
   *
   * @param JobInterface $job
   *
   * @return Drupal\Core\GeneratedUrl|string
   */
  private function getCallbackUrl(JobInterface $job) {
    $callback_url = Url::fromRoute('tmgmt_smartling.push_callback', ['job' => $job->id()])->setOptions(['absolute' => TRUE])->toString();
    $relative_callback_url = Url::fromRoute('tmgmt_smartling.push_callback', ['job' => $job->id()])->toString();
    $callback_url_host = rtrim($job->getTranslator()->getSetting('callback_url_host'), '/');

    if (!empty($callback_url_host)) {
      $callback_url = Url::fromUserInput($relative_callback_url, [
        'base_url' => $callback_url_host,
      ])->toString();
    }

    return $callback_url;
  }

  /**
   * @param array $settings
   * @return \Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper
   */
  public function getApiWrapper(array $settings) {
    ConnectorInfo::setUpCurrentClientInfo();

    $this->smartlingApiWrapper->setSettings($settings);

    return $this->smartlingApiWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $batch_uid = $job->getSetting('batch_uid');
    $api_wrapper = $this->getApiWrapper($job->getTranslator()->getSettings());
    $error_notification_message = t('File @name (job id = @job_id) wasn\'t uploaded. Please see logs for more info.', [
      '@name' => $job->getTranslatorPlugin()->getFileName($job),
      '@job_id' => $job->id(),
    ])->render();

    $api_wrapper->createAuditLogRecord(
      $job,
      NULL,
      $this->currentUser,
      CreateRecordParameters::ACTION_TYPE_UPLOAD
    );

    // Skip processing if job/batch hasn't been created.
    if (empty($batch_uid)) {
      $this->logger->error(t('File @name (job id = @job_id) wasn\'t uploaded due to previous error(s).', [
        '@name' => $job->getTranslatorPlugin()->getFileName($job),
        '@job_id' => $job->id(),
      ])->render());

      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => $error_notification_message,
        "type" => "error",
      ]);

      return;
    }

    $translation_request = $this->translationRequestManager->upsertTranslationRequest($job);

    if (empty($translation_request)) {
      $this->logger->error('Can\'t upsert translation request for file @name (job id = @job_id).', [
        '@name' => $job->getTranslatorPlugin()->getFileName($job),
        '@job_id' => $job->id(),
      ]);

      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => 'Can\'t upsert translation request. ' . $error_notification_message,
        "type" => "error",
      ]);

      return;
    }

    $name = $this->getFileName($job);
    $export_format = pathinfo($name, PATHINFO_EXTENSION);
    $export = $this->formatPluginsManager->createInstance($export_format);
    $path = $job->getSetting('scheme') . '://tmgmt_sources/' . $name;
    $dirname = dirname($path);

    if (file_prepare_directory($dirname, FILE_CREATE_DIRECTORY)) {
      $data = $export->export($job);
      $file = file_save_data($data, $path, FILE_EXISTS_REPLACE);
      $this->fileUsage->add($file, 'tmgmt_smartling', 'tmgmt_job', $job->id());
      $job->submitted('Exported file can be downloaded <a href="@link">here</a>.', array('@link' => file_create_url($path)));
    }
    else {
      $e = new \Exception('It is not possible to create a directory ' . $dirname);
      watchdog_exception('tmgmt_smartling', $e);
      $job->rejected('Job has been rejected with following error: @error',
        ['@error' => $e->getMessage()], 'error');

      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => $error_notification_message,
        "type" => "error",
      ]);

      $this->translationRequestManager->commitError($job, $translation_request, $e);

      return;
    }

    try {
      $upload_params = new UploadFileParameters();
      $upload_params->setClientLibId(BaseApiAbstract::getCurrentClientId(), BaseApiAbstract::getCurrentClientVersion());
      $upload_params->setAuthorized(0);

      if ($job->getTranslator()->getSetting('callback_url_use')) {
        $upload_params->set('callbackUrl', $this->getCallbackUrl($job));
      }

      $real_path = \Drupal::service('file_system')->realpath($file->getFileUri());
      $file_type = $export_format === 'xlf' ? 'xliff' : $export_format;
      $upload_params->setLocalesToApprove($job->getRemoteTargetLanguage());

      $api_wrapper->getApi('batch')->uploadBatchFile(
        $real_path,
        $file->getFilename(),
        $file_type,
        $batch_uid,
        $this->addSmartlingDirectives($upload_params, $job)
      );

      $message = t('File uploaded. Job id: @job_id, file name: @name.', [
        '@name' => $job->getTranslatorPlugin()->getFileName($job),
        '@job_id' => $job->id(),
      ]);

      $this->logger->info($message);

      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => $message->render(),
        "type" => "status",
      ]);

      if ($job->id() == $job->getSetting('batch_execute_on_job')) {
        $api_wrapper->executeBatch($batch_uid);

        $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
          "message" => t("Finished: content is in the job. You may need to wait a few seconds before content is authorized (if you checked 'authorize' checkbox).")
            ->render(),
          "type" => "status",
        ]);
      }

      $this->eventDispatcher->dispatch(RequestTranslationEvent::REQUEST_TRANSLATION_EVENT, new RequestTranslationEvent($job));

      if (!$this->translationRequestManager->commitSuccessfulUpload($job, $translation_request)) {
        $warning_message = 'Can\'t update submitted date for translation request = @translation_request.';
        $warning_message_context = [
          '@translation_request' => json_encode($translation_request),
        ];

        $this->logger->warning($warning_message, $warning_message_context);

        $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
          "message" => 'Can\'t update submitted date for translation request. See logs for more info.',
          "type" => "warning",
        ]);
      }
    }
    catch (Exception $e) {
      watchdog_exception('tmgmt_smartling', $e);

      $job->rejected('Job has been rejected with following error: @error uploading @file', [
        '@error' => $e->getMessage(),
        '@file' => $file->getFileUri()
      ], 'error');

      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => t('Error while uploading @file. Please see logs for more info.', [
          '@file' => $file->getFileUri()
        ])->render(),
        "type" => "error",
      ]);

      $this->translationRequestManager->commitError($job, $translation_request, $e);
    }
    // @todo disallow to submit translation to unsupported language.
  }

  /**
   * Adds smartling directives to upload parameters.
   *
   * Array of directives can be altered by `tmgmt_smartling_directives_alter`
   * hook.
   *
   * @param \Smartling\File\Params\UploadFileParameters $params
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return \Smartling\File\Params\UploadFileParameters
   */
  protected function addSmartlingDirectives(UploadFileParameters $params, JobInterface $job) {
    $directives = [
      'smartling.translate_paths' => 'html/body/div/div, html/body/div/span',
      'smartling.string_format_paths' => 'html : html/body/div/div, @default : html/body/div/span',
      'smartling.variants_enabled' => 'true',
      'smartling.source_key_paths' => 'html/body/div/{div.sl-variant}, html/body/div/{span.sl-variant}',
      'smartling.character_limit_paths' => 'html/body/div/limit',
      'smartling.placeholder_format_custom' => $job->getSetting('custom_regexp_placeholder'),
    ];

    $this->moduleHandler->alter('tmgmt_smartling_directives', $directives);

    if (is_array($directives)) {
      $directives = $this->filterDirectives($directives);

      foreach ($directives as $directive_name => $directive_value) {
        $params->set($directive_name, $directive_value);
      }
    }

    return $params;
  }

  /**
   * @param array $directives
   * @return array
   */
  protected function filterDirectives(array $directives) {
    $allowed_directives_for_xml_file = [
      'smartling.entity_escaping',
      'smartling.variants_enabled',
      'smartling.translate_paths',
      'smartling.string_format_paths',
      'smartling.placeholder_format_custom',
      'smartling.placeholder_format',
      'smartling.sltrans',
      'smartling.source_key_paths',
      'smartling.pseudo_inflation',
      'smartling.instruction_paths',
      'smartling.character_limit_paths',
      'smartling.force_inline_for_tags',
    ];

    $result = [];

    foreach ($directives as $directive_name => $directive_value) {
      if (in_array($directive_name, $allowed_directives_for_xml_file)) {
        $result[$directive_name] = $directive_value;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $languages = [];

    // Prevent access if the translator isn't configured yet.
    if (!$translator->getSetting('project_id')) {
      // @todo should be implemented by an Exception.
      return $languages;
    }
    try {
      $smartling_project_details = $this->getApiWrapper($translator->getSettings())->getApi('project')->getProjectDetails();
      foreach ($smartling_project_details['targetLocales'] as $language) {
        $languages[$language['localeId']] = $language['localeId'];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Can not get languages from the translator: @message', [
        '@message' => $e->getMessage(),
      ]);

      return $languages;
    }

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'zh-hans' => 'zh-CH',
      'nl' => 'nl-NL',
      'en' => 'en-EN'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $remote_languages = $this->getSupportedRemoteLanguages($translator);
    unset($remote_languages[$source_language]);

    return $remote_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return TRUE;
  }

  /**
   * Returns file name.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @return string
   */
  public function getFileName(JobInterface $job) {
    // TODO: identical filename task.
    // $extension = $job->getSetting('export_format');
    //
    // try {
    //   // Try to load existing file name from tmgmt_job table.
    //   $filename = $job->get('job_file_name');
    //
    //   if (!empty($filename->getValue())) {
    //     $filename = $filename->getValue()[0]['value'];
    //   }
    //   // Job item title should be included into a filename only if there is a
    //   // single JobItem in a Job. If there are 3 JobItems in a file - file name
    //   // should be "@entity_type_@entity_id>". And finally for every Job with
    //   // more than 3 JobItems - standard "JobId@id"
    //   elseif ($extension == 'xml') {
    //     $file_names = [];
    //     $job_items = $job->getItems();
    //     $job_items_count = count($job_items);
    //
    //     if ($job_items_count == 1) {
    //       $file_name_type = 'expanded';
    //     }
    //     else if ($job_items_count > 1 && $job_items_count <= 3) {
    //       $file_name_type = 'simplified';
    //     }
    //     else {
    //       $file_name_type = 'default';
    //     }
    //
    //     foreach ($job_items as $job_item) {
    //       $job_item_id = $job_item->getItemId();
    //       $job_item_type = $job_item->getItemType();
    //
    //       switch ($file_name_type) {
    //         case 'expanded':
    //           $temp_name = $job_item->getSourceLabel() . '_' . $job_item_type . '_' . $job_item_id;
    //
    //           break;
    //
    //         case 'simplified':
    //           $temp_name = $job_item_type . '_' . $job_item_id;
    //
    //           break;
    //
    //         default:
    //           $file_names[$job_item_id] = 'JobID' . $job->id() . '_' . $job->getSourceLangcode() . '_' . $job->getTargetLangcode();
    //
    //           break 2;
    //       }
    //
    //       $file_names[$job_item_id] = $temp_name;
    //     }
    //
    //     ksort($file_names);
    //     $filename = $this->cleanFileName(implode('_', $file_names) . '.' . $extension);
    //   }
    //   else {
    //     $filename = '';
    //   }
    // } catch (\Exception $e) {
    //   $filename = '';
    // }
    //
    // // Fallback to default file name.
    // if (empty($filename) || !$job->getSetting('identical_file_name')) {
    //   $filename = "JobID" . $job->id() . '_' . $job->getSourceLangcode() . '_' . $job->getTargetLangcode() . '.' . $extension;
    // }
    //
    // return $filename;

    try {
      $filename = $job->get('job_file_name');
      $filename = !empty($filename->getValue()) ? $filename->getValue()[0]['value'] : '';
    } catch (\Exception $e) {
      $filename = '';
    }

    if (empty($filename)) {
      $extension = $job->getSetting('export_format');
      $name = "JobID" . $job->id() . '_' . $job->getSourceLangcode() . '_' . $job->getTargetLangcode();

      // Alter name before saving it into database.
      $cloned_job = clone $job;
      \Drupal::moduleHandler()->alter('tmgmt_smartling_filename', $name, $cloned_job);

      $filename = $name . '.' . $extension;
    }

    return $filename;
  }

  /**
   * Return clean filename, sanitized for path traversal vulnerability.
   *
   * Url (https://code.google.com/p/teenage-mutant-ninja-turtles
   * /wiki/AdvancedObfuscationPathtraversal).
   *
   * @param string $filename
   *   File name.
   * @param bool $allow_dirs
   *   TRUE if allow dirs. FALSE by default.
   *
   * @return string
   *   Return clean filename.
   */
  private function cleanFileName($filename, $allow_dirs = FALSE) {
    // Prior to PHP 5.5, empty() only supports variables.
    // (http://www.php.net/manual/en/function.empty.php).
    $trim_filename = trim($filename);
    if (empty($trim_filename)) {
      return '';
    }

    $pattern = '/[^a-zA-Z0-9_\-\:]/i';
    $info = pathinfo(trim($filename));
    $filename = preg_replace($pattern, '_', $info['filename']);
    if (isset($info['extension']) && !empty($info['extension'])) {
      $filename .= '.' . preg_replace($pattern, '_', $info['extension']);
    }

    if ($allow_dirs && isset($info['dirname']) && !empty($info['dirname'])) {
      $filename = preg_replace('/[^a-zA-Z0-9_\/\-\:]/i', '_', $info['dirname']) . '/' . $filename;
    }

    return (string) $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return array(
      'export_format' => 'xml',
      'allow_override' => TRUE,
      'scheme' => 'public',
      'retrieval_type' => 'published',
      'callback_url_use' => FALSE,
      'callback_url_host' => '',
      'auto_authorize_locales' => TRUE,
      'xliff_processing' => TRUE,
      'context_silent_user_switching' => FALSE,
      'custom_regexp_placeholder' => '(@|%|!)[\w-]+',
      'context_skip_host_verifying' => FALSE,
      'identical_file_name' => FALSE,
      'enable_smartling_logging' => TRUE,
      'enable_notifications' => TRUE,
      'async_mode' => FALSE,
      'enable_basic_auth' => FALSE,
      'basic_auth' => [
        'login' => '',
        'password' => '',
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();
    foreach ($job_items as $job_item) {
      //tmgmt_smartling_download_file($job_item->getJob());
      $this->requestTranslation($job_item->getJob());

      if ($job->isContinuous()) {
        $job_item->active();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isReadyForDownload(JobInterface $job) {
    return $this->translationRequestManager->isTranslationRequestReadyForDownload($job);
  }

  public function abortTranslation(JobInterface $job) {
    $api_wrapper = $this->getApiWrapper($job->getTranslator()->getSettings());
    $api_wrapper->createAuditLogRecord(
      $job,
      NULL,
      $this->currentUser,
      CreateRecordParameters::ACTION_TYPE_CANCEL
    );

    return parent::abortTranslation($job);
  }

  /**
   * Downloads translation file and applies it.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return bool
   */
  public function downloadTranslation(JobInterface $job) {
    return tmgmt_smartling_download_file($job);
  }

  /**
   * {@inheritdoc}
   */
  public function cancelTranslation(JobInterface $job) {
    // TODO: Implement cancelTranslation() method.
  }

  /**
   * Requests translation.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @param array $data
   *
   * @return mixed
   */
  public function requestTranslationExtended(JobInterface $job, array $data) {
    // Pass queue item data into job settings.
    $settings_map_item = $job->settings->get(0);

    if ($settings_map_item) {
      $settings = $settings_map_item->getValue();

      if (isset($data['batch_uid'])) {
        $settings['batch_uid'] = $data['batch_uid'];
      }

      if ($data['batch_execute_on_job']) {
        $settings['batch_execute_on_job'] = $data['batch_execute_on_job'];
      }

      $job->settings->set(0, $settings);
    }

    $this->requestTranslation($job);
  }

}
