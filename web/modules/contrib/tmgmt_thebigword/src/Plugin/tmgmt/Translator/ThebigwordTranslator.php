<?php

namespace Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\RemoteMappingInterface;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;

/**
 * Thebigword translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "thebigword",
 *   label = @Translation("thebigword"),
 *   description = @Translation("Thebigword translator service."),
 *   ui = "Drupal\tmgmt_thebigword\ThebigwordTranslatorUi",
 *   logo = "icons/thebigword.svg",
 * )
 */
class ThebigwordTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

  /**
   * Primary review translator state.
   */
  const JOB_ITEM_STATE_PRIMARY_REVIEW = 'thebigword_primary_review';

  /**
   * Secondary review translator state.
   */
  const JOB_ITEM_STATE_SECONDARY_REVIEW = 'thebigword_secondary_review';

  /**
   * thebigword statesource.
   */
  const STATE_SOURCE = 'TranslatableSource';

  /**
   * thebigword stateprimary external review.
   */
  const STATE_PRIMARY_REVIEW = 'TranslatablePrimaryReview';

  /**
   * thebigword statesecondary external review.
   */
  const STATE_SECONDARY_REVIEW = 'TranslatableSecondaryReview';

  /**
   * thebigword statereview preview.
   */
  const STATE_REVIEW_PREVIEW = 'TranslatableReviewPreview';

  /**
   * thebigword statereview preview.
   */
  const STATE_PREVIEW_URL = 'ResourcePreviewUrl';

  /**
   * thebigword statereview preview.
   */
  const STATE_SECOND_REVIEW_PREVIEW = 'TranslatableSecondReviewPreview';

  /**
   * thebigword statereview preview.
   */
  const STATE_SECOND_REVIEW_URL = 'ResourcePreviewUrlReview';

  /**
   * thebigword statetranslation complete.
   */
  const STATE_COMPLETE = 'TranslatableComplete';

  /**
   * The translator.
   *
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Temporary cache of pending files by state.
   *
   * @var array
   */
  protected $pendingFilesByState = [];

  /**
   * Constructs a ThebigwordTranslator object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ClientInterface $client, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \GuzzleHttp\ClientInterface $client */
    $client = $container->get('http_client');
    return new static(
      $client,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Sets a Translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator to set.
   */
  public function setTranslator(TranslatorInterface $translator) {
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return [
      'review_tool' => [
        'checkout' => [
          'default' => TRUE,
          'disable' => FALSE,
        ],
        'access' => [
          'primary' => FALSE,
          'secondary' => FALSE,
        ],
      ],
      'user_information_control' => [
        'create' => TRUE,
        'review' => TRUE,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $supported_remote_languages = [];
    $this->setTranslator($translator);

    if (empty($this->translator->getSetting('client_contact_key'))) {
      return $supported_remote_languages;
    }

    try {
      $supported_languages = $this->request('languages', 'GET', []);

      // Parse languages.
      foreach ($supported_languages as $language) {
        $supported_remote_languages[$language['CultureName']] = $language['DisplayName'];
      }
    }
    catch (\Exception $e) {
      // Ignore exception, nothing we can do.
    }
    asort($supported_remote_languages);
    return $supported_remote_languages;
  }

  /**
   * Returns list of expertise options.
   *
   * @param JobInterface $job
   *   Job object.
   *
   * @return array
   *   List of expertise options, keyed by their code.
   */
  public function getCategory(JobInterface $job) {
    return [
      1 => 'Generic / Universal',
      2 => 'Agriculture & Horticulture',
      3 => 'Architecture & Construction',
      4 => 'Arts & Culture',
      5 => 'Automotive & Transport',
      6 => 'Banking & Finance',
      7 => 'Business & Commerce',
      8 => 'Communication & Media',
      9 => 'Compliance',
      10 => 'Computer Hardware & Telecommunications',
      11 => 'Computer Software & Networking',
      12 => 'Electrical Engineering / Electronics',
      13 => 'Energy & Environment',
      14 => 'Food & Drink',
      15 => 'General Healthcare',
      16 => 'Law & Legal',
      17 => 'Manufacturing / Industry',
      18 => 'Marketing, Advertising & Fashion',
      19 => 'Mechanical Engineering / Machinery',
      20 => 'Military & Defence',
      21 => 'Pharmaceutical & Clinical trials',
      22 => 'Science & Chemicals',
      23 => 'Specialist Healthcare (Machinery)',
      24 => 'Specialist Healthcare (Practise)',
      25 => 'Sports, Entertainment & Gaming',
      26 => 'Travel Hospitality & Tourism',
      27 => 'UK Gov (EU based)',
      28 => 'UK Government',
      29 => 'Veterinary Sciences',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('client_contact_key')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->toUrl()->toString(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $job = $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = Job::load(reset($job_items)->getJobId());
    $this->setTranslator($job->getTranslator());
    $project_id = 0;
    $required_by = $job->getSetting('required_by');
    $datetime = new DrupalDateTime('+' . $required_by . ' weekday', 'UTC');
    $datetime = $datetime->format('Y-m-d\TH:i:s');

    try {
      $project_id = $this->newTranslationProject($job, $datetime);
      $job->addMessage('Created a new project in thebigword with the id: @id', ['@id' => $project_id], 'debug');

      /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
      foreach ($job_items as $job_item) {
        $file_id = $this->sendFiles($job_item, $project_id, $datetime);

        /** @var \Drupal\tmgmt\Entity\RemoteMapping $remote_mapping */
        $remote_mapping = RemoteMapping::create([
          'tjid' => $job->id(),
          'tjiid' => $job_item->id(),
          'remote_identifier_1' => 'tmgmt_thebigword',
          'remote_identifier_2' => $project_id,
          'remote_identifier_3' => $file_id,
          'remote_data' => [
            'FileStateVersion' => 1,
            'TmsState' => static::STATE_SOURCE,
            'RequiredBy' => $datetime,
          ],
        ]);
        $remote_mapping->save();

        if ($job_item->getJob()->isContinuous()) {
          $job_item->active();
        }
      }
      // Confirm is required to trigger the translation.
      $confirmed = $this->confirmUpload($project_id, 'ReferenceAdd');
      if ($confirmed != count($job_items)) {
        $message = 'Not all the references had been confirmed.';
        throw new TMGMTException($message);
      }
      $confirmed = $this->confirmUpload($project_id, static::STATE_SOURCE);
      if ($confirmed != count($job_items)) {
        $message = 'Not all the sources had been confirmed.';
        throw new TMGMTException($message);
      }
    }
    catch (TMGMTException $e) {
      try {
        if ($project_id) {
          $this->sendFileError('RestartPoint03', $project_id, '', $job, $datetime, $e->getMessage(), TRUE);
        }
      }
      catch (TMGMTException $e) {
        \Drupal::logger('tmgmt_thebigword')->error('Error sending the error file: @error', ['@error' => $e->getMessage()]);
      }
      $job->rejected('Job has been rejected with following error: @error',
        ['@error' => $e->getMessage()], 'error');
      if (isset($remote_mapping)) {
        $remote_mapping->delete();
      }
    }
    return $job;
  }

  /**
   * Does a request to thebigword services.
   *
   * @param string $path
   *   Resource path.
   * @param string $method
   *   (Optional) HTTP method (GET, POST...). By default uses GET method.
   * @param array $params
   *   (Optional) Form parameters to send to thebigword service.
   * @param bool $download
   *   (Optional) If we expect resource to be downloaded. FALSE by default.
   * @param bool $code
   *   (Optional) If we want to return the status code of the call. FALSE by
   *   default.
   *
   * @return array
   *   Response array from thebigword.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function request($path, $method = 'GET', $params = [], $download = FALSE, $code = FALSE) {
    $options = [];
    if (!$this->translator) {
      throw new TMGMTException('There is no Translator entity. Access to the client contact key is not possible.');
    }

    $url = $this->translator->getSetting('service_url') . '/' . $path;
    $config = \Drupal::configFactory()->get('tmgmt_thebigword.settings');

    try {
      if ($method == 'GET') {
        $options['query'] = $params;
      }
      else {
        $options['json'] = $params;
      }
      $options['headers'] = [
        'TMS-REQUESTER-ID' => $this->translator->getSetting('client_contact_key'),
      ];
      $response = $this->client->request($method, $url, $options);
    }
    catch (RequestException $e) {
      if (!$e->hasResponse()) {
        if ($code) {
          return $e->getCode();
        }
        throw new TMGMTException('Unable to connect to thebigword service due to following error: @error', ['@error' => $e->getMessage()], $e->getCode());
      }
      $response = $e->getResponse();
      if ($config->get('debug')) {
        \Drupal::logger('tmgmt_thebigword')->error('%method Request to %url:<br>
            <ul>
                <li>Request: %request</li>
                <li>Response: %response</li>
            </ul>
            ', [
              '%method' => $method,
              '%url' => $url,
              '%request' => (string) $e->getRequest()->getBody(),
              '%response' => (string) $response->getBody(),
            ]
        );
      }
      if ($code) {
        return $response->getStatusCode();
      }
      throw new TMGMTException('Unable to connect to thebigword service due to following error: @error', ['@error' => $response->getReasonPhrase()], $response->getStatusCode());
    }
    $received_data = $response->getBody()->getContents();
    if ($config->get('debug')) {
      \Drupal::logger('tmgmt_thebigword')->debug('%method Request to %url:<br>
            <ul>
                <li>Request: %request</li>
                <li>Response: %response</li>
            </ul>
            ', [
              '%method' => $method,
              '%url' => $url,
              '%request' => json_encode($options),
              '%response' => $received_data,
            ]
      );
    }
    if ($code) {
      return $response->getStatusCode();
    }

    if ($response->getStatusCode() != 200) {
      throw new TMGMTException('Unable to connect to the thebigword service due to following error: @error at @url',
        ['@error' => $response->getStatusCode(), '@url' => $url]);
    }

    // If we are expecting a download, just return received data.
    if ($download) {
      return $received_data;
    }
    $received_data = json_decode($received_data, TRUE);

    return $received_data;
  }

  /**
   * Creates new translation project at thebigword.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job.
   * @param string $required_by
   *   The date by when the translation is required.
   *
   * @return int
   *   Thebigword project id.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function newTranslationProject(JobInterface $job, $required_by) {
    $url = Url::fromRoute('tmgmt_thebigword.callback');

    // Get the project reference and po number, fall back to job ID if empty.
    $project_reference = trim($job->getSetting('project_reference'));
    $po_number = trim($job->getSetting('po_number'));
    $params = [
      'PurchaseOrderNumber' => $po_number ?: '-',
      'ProjectReference' => $project_reference ?: $job->id(),
      'RequiredByDateUtc' => $required_by,
      'QuoteRequired' => $job->getSetting('quote_required') ? 'true' : 'false',
      'SpecialismId' => $job->getSetting('category'),
      'ProjectMetadata' => [
        ['MetadataKey' => 'Response Service Base URL', 'MetadataValue' => \Drupal::request()->getSchemeAndHttpHost()],
        ['MetadataKey' => 'Response Service Path', 'MetadataValue' => $url->toString()],
      ],
    ];

    // provide either username and e-mail or just the user ID based on the user
    // information control setting.
    $settings = NestedArray::mergeDeep($this->defaultSettings(), $job->getTranslator()->getSettings());

    if (!empty($settings['user_information_control']['create'])) {
      $mail = empty($job->getOwner()->getEmail()) ? \Drupal::config('system.site')->get('mail') : $job->getOwner()->getEmail();
      $params['ProjectMetadata'][] = ['MetadataKey' => 'CMS User Name', 'MetadataValue' => $job->getOwner()->getDisplayName()];
      $params['ProjectMetadata'][] = ['MetadataKey' => 'CMS User Email', 'MetadataValue' => $mail];
    }
    else {
      $params['ProjectMetadata'][] = ['MetadataKey' => 'CMS User ID', 'MetadataValue' => $job->getOwner()->id()];
    }

    // When the review tool is enabled, also submit whether the external
    // primary/secondary review should be enabled.
    if ($job->getSetting('review')) {
      $params['ProjectMetadata'][] = [
        'MetadataKey' => 'Workflow Options',
        'MetadataValue' => 'Localize and Review',
      ];

      $params['ProjectMetadata'][] = [
        'MetadataKey' => 'External Primary Review',
        'MetadataValue' => !empty($settings['review_tool']['access']['primary']) ? 'Enabled' : 'Disabled',
      ];

      $params['ProjectMetadata'][] = [
        'MetadataKey' => 'External Secondary Review',
        'MetadataValue' => !empty($settings['review_tool']['access']['secondary']) ? 'Enabled' : 'Disabled',
      ];
    }
    else {
      $params['ProjectMetadata'][] = [
        'MetadataKey' => 'Workflow Options',
        'MetadataValue' => 'Localize Only',
      ];
    }

    return $this->request('project', 'POST', $params);
  }

  /**
   * Send the files to thebigword.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The Job.
   * @param int $project_id
   *   Thebigword project id.
   * @param string $required_by
   *   The date by when the translation is required.
   *
   * @return string
   *   Thebigword FileId.
   */
  private function sendFiles(JobItemInterface $job_item, $project_id, $required_by) {
    /** @var \Drupal\tmgmt_file\Format\FormatInterface $xliff_converter */
    $xliff_converter = \Drupal::service('plugin.manager.tmgmt_file.format')->createInstance('xlf');

    $job_item_id = $job_item->id();
    $target_language = $job_item->getJob()->getRemoteTargetLanguage();
    $conditions = ['tjiid' => ['value' => $job_item_id]];
    $xliff = $xliff_converter->export($job_item->getJob(), $conditions);
    $name = "JobID_{$job_item->getJob()->id()}_JobItemID_{$job_item_id}_{$job_item->getJob()->getSourceLangcode()}_{$target_language}";

    $file_id = $this->uploadFileResource($xliff, $job_item, $project_id, $name, $required_by);

    $this->sendUrl($job_item, $project_id, $file_id, FALSE, $required_by);

    return $file_id;
  }

  /**
   * Creates a file resource at thebigword.
   *
   * @param string $xliff
   *   .XLIFF string to be translated. It is send as a file.
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The Job item.
   * @param string $project_id
   *   The Project ID.
   * @param string $name
   *   File name of the .XLIFF file.
   * @param string $required_by
   *   The date by when the translation is required.
   *
   * @return string
   *   Thebigword uuid of the resource.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function uploadFileResource($xliff, JobItemInterface $job_item, $project_id, $name, $required_by) {
    $form_params = [
      'ProjectId' => $project_id,
      'RequiredByDateUtc' => $required_by,
      'SourceLanguage' => $job_item->getJob()->getRemoteSourceLanguage(),
      'TargetLanguage' => $job_item->getJob()->getRemoteTargetLanguage(),
      'FilePathAndName' => "$name.xliff",
      'FileState' => static::STATE_SOURCE,
      'FileData' => base64_encode($xliff),
    ];
    /** @var int $file_id */
    $file_id = $this->request('file', 'POST', $form_params);

    return $file_id;
  }

  /**
   * Parses received translation from thebigword and returns unflatted data.
   *
   * @param string $data
   *   Base64 encode data, received from thebigword.
   *
   * @return array
   *   Unflatted data.
   */
  protected function parseTranslationData($data) {
    /** @var \Drupal\tmgmt_file\Format\FormatInterface $xliff_converter */
    $xliff_converter = \Drupal::service('plugin.manager.tmgmt_file.format')->createInstance('xlf');
    // Import given data using XLIFF converter. Specify that passed content is
    // not a file.
    return $xliff_converter->import($data, FALSE);
  }

  /**
   * Fetches translations for job items of a given job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   A job containing job items that translations will be fetched for.
   * @param int|null $project_id
   *   (optional) A project ID, if not provided, the project ID from the first
   *   remote mapping will be used.
   * @param int|null $file_id
   *   (optional) A file ID, if provided, only that file for the given job
   *   will be checked and the project ID will be ignored.
   *
   * @return array
   *   A array with update counts for the following keys: @preview, @review,
   *   @translation.
   */
  public function fetchTranslatedFiles(JobInterface $job, $project_id = NULL, $file_id = NULL) {
    $this->setTranslator($job->getTranslator());
    $updates = [
      '@preview' => 0,
      '@review' => 0,
      '@translation' => 0,
    ];
    $errors = [];

    try {
      if (!$project_id) {
        $mappings = RemoteMapping::loadByLocalData($job->id());
        // If there are no mappings yet, then there this is a continuous job that
        // had no job items yet.
        if (!$mappings) {
          return [];
        }
        $mapping = reset($mappings);
        $project_id = $mapping->getRemoteIdentifier2();
      }

      // Get the files of this job.
      $files = $this->getPendingFiles($project_id, $file_id);
      /** @var JobItemInterface $job_item */
      foreach ($files as $file_id => $file) {
        /** @var \Drupal\tmgmt\Entity\RemoteMapping $mapping */
        $mappings = RemoteMapping::loadByRemoteIdentifier('tmgmt_thebigword', $project_id, $file_id);
        $mapping = reset($mappings);
        try {
          $this->addFileDataToJob($mapping, $file['CmsState']);

          $update_key = $this->getUpdateKey($file['CmsState']);
          $updates[$update_key]++;
        }
        catch (TMGMTException $e) {

          // Mark the file as failed, to prevent it from being processed again.
          $form_params = [
            'FileId' => $file_id,
            'CmsState' => $file['CmsState'] . '-Error',
          ];
          $this->request('file/cmsstate', 'POST', $form_params);

          $restart_point = $this->getErrorRestartPoint($file['CmsState']);
          $this->sendFileError($restart_point, $project_id, $file_id, $job, $mapping->getRemoteData('RequiredBy'), $file['CmsState'] . ':' . $e->getMessage());
          $job->addMessage('Error fetching the job item: @job_item.', ['@job_item' => $mapping->getJobItem()->label()], 'error');
          $errors[] = [
            'ProjectId' => $project_id,
            'RestartPoint' => $restart_point,
          ];
        }
      }
    }
    catch (TMGMTException $e) {
      \Drupal::logger('tmgmt_thebigword')->error('Could not pull translation resources: @error', ['@error' => $e->getMessage()]);
    }
    if (!empty($errors)) {
      foreach ($errors as $error) {
        $this->confirmUpload($error['ProjectId'], $error['RestartPoint']);
      }
    }
    return $updates;
  }

  /**
   * Returns the key for the update report.
   *
   * @param string $state
   *   The current CMS state.
   *
   * @return string
   *   One of: @preview, @review, @translation.
   *
   * @see ::fetchTranslatedFiles()
   */
  protected function getUpdateKey($state) {
    if (in_array($state, [static::STATE_REVIEW_PREVIEW, static::STATE_SECOND_REVIEW_PREVIEW])) {
      return '@preview';
    }
    if (in_array($state, [static::STATE_PRIMARY_REVIEW, static::STATE_SECONDARY_REVIEW])) {
      return '@review';
    }
    return '@translation';
  }

  /**
   * Send the preview url.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The Job item.
   * @param int $project_id
   *   The project ID.
   * @param string $file_id
   *   The file ID.
   * @param bool $preview
   *   If true will send the preview URL, otherwise the source URL.
   * @param string $required_by
   *   The date by when the translation is required.
   * @param string $cms_state
   *   Either ::STATE_REVIEW_PREVIEW or ::STATE_SECOND_REVIEW_PREVIEW
   *
   * @return string
   *   The file ID;
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  protected function sendUrl(JobItemInterface $job_item, $project_id, $file_id, $preview, $required_by, $cms_state = self::STATE_REVIEW_PREVIEW) {
    /** @var Url $url */
    $url = $job_item->getSourceUrl();
    $state = 'ReferenceAdd';
    $name = 'source-url';
    if ($preview) {
      $source_plugin = $job_item->getSourcePlugin();
      if ($source_plugin instanceof SourcePreviewInterface) {
        $url = $source_plugin->getPreviewUrl($job_item);
      }
      $state = $cms_state == static::STATE_REVIEW_PREVIEW ? static::STATE_PREVIEW_URL : static::STATE_SECOND_REVIEW_URL;
      $name = 'preview-url';
    }
    if (!$url) {
      $url = Url::fromRoute('tmgmt_thebigword.no_preview');
    }
    $preview_data = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE PreviewUrl SYSTEM "http://www.thebigword.com/dtds/PreviewUrl.dtd">
<PreviewUrl>' . $url->setAbsolute()->toString() . '</PreviewUrl>';

    $form_params = [
      'ProjectId' => $project_id,
      'RequiredByDateUtc' => $required_by,
      'SourceLanguage' => $job_item->getJob()->getRemoteSourceLanguage(),
      'TargetLanguage' => $job_item->getJob()->getRemoteTargetLanguage(),
      'FilePathAndName' => "$name.xml",
      'FileState' => $state,
      'FileData' => base64_encode($preview_data),
      'FileIdToUpdate' => $file_id,
    ];
    $file_id = $this->request('file', 'PUT', $form_params);

    if ($preview) {
      $this->confirmUpload($project_id, $state);
    }

    return $file_id;
  }

  /**
   * Returns file information for pending files in a given state.
   *
   * @param string $state
   *   One of the thebigword file states.
   *
   * @return array
   *   A list of files in the given state.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  protected function getPendingFilesForState($state) {
    if (!isset($this->pendingFilesByState[$state])) {
      $file_infos = $this->request('fileinfos/' . $state);

      $this->pendingFilesByState[$state] = [];
      foreach ($file_infos as $file_info) {
        $this->pendingFilesByState[$state][$file_info['FileId']] = $file_info;
      }
    }

    return $this->pendingFilesByState[$state];
  }

  /**
   * Returns file information for pending files for a given project.
   *
   * @param int $project_id
   *   (optional) A project ID, if not provided, the project ID from the first
   *   remote mapping will be used.
   * @param int $file_id
   *   (optional) A file ID, if provided, only that file for the given job
   *   will be checked and the project ID will be ignored.
   *
   * @return array
   *   The file infos.
   */
  protected function getPendingFiles($project_id = NULL, $file_id = NULL) {
    $pending_files = [];
    $all_files = [];
    if ($file_id) {
      $file = $this->request('file/cmsstate/' . $file_id);
      if ($file) {
        $all_files = [$file];
      }
    }
    elseif ($project_id) {
      $all_files = (array) $this->request('project/cmsstates/' . $project_id, 'GET', ['FileNameMatch' => '(?i)^.*$']);
    }
    foreach ($all_files as $file) {

      // Ignore files that are not in one of the supported states.
      if (in_array($file['CmsState'], [static::STATE_COMPLETE, static::STATE_REVIEW_PREVIEW, static::STATE_SECOND_REVIEW_PREVIEW, static::STATE_PRIMARY_REVIEW, static::STATE_SECONDARY_REVIEW])) {
        $pending_files[$file['FileId']] = $file;
      }
    }

    return $pending_files;
  }

  /**
   * Sends an error file to thebigword.
   *
   * @param string $state
   *   The state.
   * @param int $project_id
   *   The project id.
   * @param string $file_id
   *   The file id to update.
   * @param \Drupal\tmgmt\JobInterface $job
   *   The Job.
   * @param string $required_by
   *   The date by when the translation is required.
   * @param string $message
   *   (Optional) The error message.
   * @param bool $confirm
   *   (Optional) Set to TRUE if also want to send the confirmation message
   *   of this error. Otherwise will not send it.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   If there is a problem with the request.
   */
  public function sendFileError($state, $project_id, $file_id, JobInterface $job, $required_by, $message = '', $confirm = FALSE) {
    $form_params = [
      'ProjectId' => $project_id,
      'RequiredByDateUtc' => $required_by,
      'SourceLanguage' => $job->getRemoteSourceLanguage(),
      'TargetLanguage' => $job->getRemoteTargetLanguage(),
      'FilePathAndName' => 'error-' . (new DrupalDateTime())->format('Y-m-d\TH:i:s') . '.txt',
      'FileIdToUpdate' => $file_id,
      'FileState' => $state,
      'FileData' => base64_encode($message),
    ];
    $this->request('file', 'PUT', $form_params);
    if ($confirm) {
      $this->confirmUpload($project_id, $state);
    }
  }

  /**
   * Retrieve the data of a file in a state.
   *
   * @param \Drupal\tmgmt\RemoteMappingInterface $mapping
   *   The remote mapping entity.
   * @param string $state
   *   The state of the file.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function processLoginToken(RemoteMappingInterface $mapping, $state) {
    $file_id = $mapping->getRemoteIdentifier3();
    $data = $this->request('file/' . $state . '/' . $file_id);
    $decoded_data = base64_decode($data['FileData']);

    $xml = simplexml_load_string($decoded_data);

    $job_item = $mapping->getJobItem();
    if ($state == static::STATE_PRIMARY_REVIEW) {
      $job_item->setTranslatorState(ThebigwordTranslator::JOB_ITEM_STATE_PRIMARY_REVIEW);
      $job_item->addMessage('Ready for primary review in Review Tool.');
    }
    elseif ($state == static::STATE_SECONDARY_REVIEW) {
      $job_item->setTranslatorState(ThebigwordTranslator::JOB_ITEM_STATE_SECONDARY_REVIEW);
      $job_item->addMessage('Ready for secondary review in Review Tool.');
    }
    $job_item->save();

    $mapping->addRemoteData('TaskId', (string) $xml->GmsJob->TaskId);
    $mapping->addRemoteData('DirectAccessToken', (string) $xml->GmsJob->DirectAccessToken);
    $mapping->addRemoteData('DirectAccessSharedSecret', (string) $xml->GmsJob->DirectAccessSharedSecret);
    $mapping->addRemoteData('DirectAccessEndPoint', (string) $xml->GmsJob->DirectAccessEndPoint);
    $mapping->save();

    // Confirm that we download the file.
    $form_params = [
      'FileId' => $file_id,
      'FileState' => $state,
    ];
    $this->request('fileinfo/downloaded', 'POST', $form_params);
    $form_params = [
      'FileId' => $file_id,
      'CmsState' => $state . '-Success',
    ];
    $this->request('file/cmsstate', 'POST', $form_params);
  }

  /**
   * Retrieve the data of a file in a state.
   *
   * @param \Drupal\tmgmt\RemoteMappingInterface $mapping
   *   The remote mapping entity.
   * @param string $state
   *   The state of the file.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function addFileDataToJob(RemoteMappingInterface $mapping, $state) {
    if (in_array($state, [static::STATE_PRIMARY_REVIEW, static::STATE_SECONDARY_REVIEW])) {
      $this->processLoginToken($mapping, $state);
      return;
    }

    $file_id = $mapping->getRemoteIdentifier3();
    $data = $this->request('file/' . $state . '/' . $file_id);
    $decoded_data = base64_decode($data['FileData']);
    $file_data = $this->parseTranslationData($decoded_data);
    if ($state == static::STATE_COMPLETE) {
      $status = TMGMT_DATA_ITEM_STATE_TRANSLATED;
    }
    else {
      $status = TMGMT_DATA_ITEM_STATE_PRELIMINARY;
    }

    $job = $mapping->getJob();
    $job->addTranslatedData($file_data, [], $status);
    // Confirm that we download the file.
    $form_params = [
      'FileId' => $file_id,
      'FileState' => $state,
    ];
    $this->request('fileinfo/downloaded', 'POST', $form_params);
    $form_params = [
      'FileId' => $file_id,
      'CmsState' => $state . '-Success',
    ];
    $this->request('file/cmsstate', 'POST', $form_params);

    $mapping->removeRemoteData('TmsState');
    $mapping->addRemoteData('TmsState', $state);
    $mapping->save();

    // If this is a preliminary translation we must send the preview url.
    if ($state != static::STATE_COMPLETE && $job->getSetting('review')) {
      $this->sendUrl($mapping->getJobItem(), $mapping->getRemoteIdentifier2(), $file_id, TRUE, $mapping->getRemoteData('RequiredBy'), $state);
    }
  }

  /**
   * Checks if a user is allowed to do the external review of a job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   *
   * @return bool
   *   TRUE if the job item is in an external review state and the user is
   *   allowed to review.
   */
  public function userHasExternalReviewAccess(JobItemInterface $job_item, AccountInterface $user) {
    // Ensure we have a user entity.
    $user = User::load($user->id());

    $permission = NULL;
    switch ($job_item->getTranslatorState()) {
      case ThebigwordTranslator::JOB_ITEM_STATE_PRIMARY_REVIEW:
        $permission = 'access tmgmt thebigword primary review';
        break;

      case ThebigwordTranslator::JOB_ITEM_STATE_SECONDARY_REVIEW:
        $permission = 'access tmgmt thebigword primary review';
        break;
    }

    if (!$permission || !$user->hasPermission($permission)) {
      return FALSE;
    }

    if (!$this->checkSkills($user, $job_item->getJob()->getSourceLangcode(), $job_item->getJob()->getTargetLangcode())) {
      return FALSE;
    }

    // @todo Implement skills check.
    return TRUE;
  }

  /**
   * Checks if a user has the necessary language skills.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $langcode_from
   *   The from language code.
   * @param string $langcode_to
   *   The to language code.
   *
   * @return bool
   *   TRUE if the user has the necessary language skill.
   */
  public function checkSkills(UserInterface $user, $langcode_from, $langcode_to) {
    if ($user->hasField('tmgmt_thebigword_skills')) {
      foreach ($user->get('tmgmt_thebigword_skills') as $item) {
        if ($item->language_from == $langcode_from && $item->language_to == $langcode_to) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns the correct error restart point based on the current state.
   *
   * @param string $state
   *   One of the state constants.
   *
   * @return string
   *   The correct restart point.
   */
  public function getErrorRestartPoint($state) {
    switch ($state) {
      case static::STATE_SOURCE;
      case static::STATE_REVIEW_PREVIEW;
      case static::STATE_SECOND_REVIEW_PREVIEW;
        return 'RestartPoint01';

      case static::STATE_COMPLETE;
        return 'RestartPoint02';

      // RestartPoint03 is used if the initial submission fails.

      case static::STATE_PRIMARY_REVIEW;
      case static::STATE_SECONDARY_REVIEW;
        return 'RestartPoint04';
    }
  }

  /**
   * Confirm all the files uploaded in a project for a state.
   *
   * @param int $project_id
   *   The project ID.
   * @param string $state
   *   The state.
   *
   * @return array
   *   The number of confirmed files.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  protected function confirmUpload($project_id, $state) {
    $form_params = [
      'ProjectId' => $project_id,
      'FileState' => $state,
    ];
    return $confirmed = $this->request('fileinfos/uploaded', 'POST', $form_params);
  }

}
