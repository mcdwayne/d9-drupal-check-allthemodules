<?php

/**
 * @file
 * Contains \Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator.
 */

namespace Drupal\tmgmt_oht\Plugin\tmgmt\Translator;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Translator\AvailableResult;

/**
 * OHT translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "oht",
 *   label = @Translation("One Hour Translation"),
 *   description = @Translation("One Hour Translation provides professional, fast and affordable human translation services, available 24/7."),
 *   ui = "Drupal\tmgmt_oht\OhtTranslatorUi",
 *   logo = "icons/oht.svg",
 * )
 */
class OhtTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Translation service URL.
   */
  const PRODUCTION_URL = 'https://api.onehourtranslation.com/api';

  /**
   * Translation sandbox service URL.
   */
  const SANDBOX_URL = 'https://sandbox.onehourtranslation.com/api';

  /**
   * Translation service API version.
   *
   * @var string
   */
  const API_VERSION = '2';

  /**
   * The translator.
   *
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * Sets a Translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   */
  public function setTranslator(TranslatorInterface $translator) {
    if (!isset($this->translator)) {
      $this->translator = $translator;
    }
  }

  /**
   * Flag to trigger debug watchdog logging of requests.
   *
   * Use variable_set('tmgmt_oht_debug', TRUE); to toggle debugging.
   *
   * @var bool
   */
  private $debug = FALSE;

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * List of default Drupal 8 and OHT language mapping.
   *
   * @var array
   */
  protected $ohtLanguagesMapping = array(
    'af' => 'af',
    'ar' => 'ar-sa',
    'az' => 'az-az',
    'bg' => 'bg-bg',
    'bn' => 'bn-bd',
    'bs' => 'bs-ba',
    'ca' => 'ca-es',
    'cs' => 'cs-cz',
    'da' => 'da',
    'de' => 'de-de',
    'el' => 'el-gr',
    'en' => 'en-us',
    // 'es-ar' => 'es-ar', // Not supported in Drupal.
    'es' => 'es-es',
    'et' => 'et-ee',
    // 'fa' => 'fa-af', // Not supported in Drupal.
    'fa' => 'fa-ir',
    'fi' => 'fi-fi',
    // 'fl-be' => 'fl-be', // Not supported in Drupal.
    // 'fl-fl' => 'fl-fl', // Not supported in Drupal.
    'fr' => 'fr-fr',
    // 'fr' => 'fr-ca', // Not supported in Drupal.
    'gu' => 'gu-in',
    'he' => 'he-il',
    'hi' => 'hi-in',
    'hr' => 'hr-hr',
    'ht' => 'ht',
    'hu' => 'hu-hu',
    'hy' => 'hy-am',
    'id' => 'id-id',
    'is' => 'is-is',
    'it' => 'it-it',
    'ja' => 'ja-jp',
    'ka' => 'ka-ge',
    'kk' => 'kk-kz',
    'km' => 'km-kh',
    'ko' => 'ko-kp',
    'ku' => 'ku-tr',
    'lt' => 'lt-lt',
    'lv' => 'lv-lv',
    'mk' => 'mk-mk',
    'mr' => 'mr-in',
    'ms' => 'ms-my',
    'nl' => 'nl-nl',
    'nb' => 'no-no', // We are using Norwegian Bokmål.
    'pa' => 'pa-in',
    'pl' => 'pl-pl',
    'ps' => 'ps',
    'pt' => 'pt-pt',
    'pt-br' => 'pt-br',
    'pt-pt' => 'pt-pt',
    'ro' => 'ro-ro',
    'ru' => 'ru-ru',
    'sa' => 'sa-in',
    'sk' => 'sk-sk',
    'sl' => 'sl-si',
    'sq' => 'sq-al',
    'sr' => 'sr-rs',
    'sv' => 'sv-se',
    'ta' => 'ta-in',
    'th' => 'th-th',
    'tl' => 'tl-ph',
    'tr' => 'tr-tr',
    'uk' => 'uk-ua',
    'ur' => 'ur-pk',
    'uz' => 'uz-uz',
    'vi' => 'vi-vn',
    'zh-hans' => 'zh-cn-cmn-s',
    'zh-hant' => 'zh-cn-cmn-t',
    // 'zh-cn-yue' => 'zh-cn-yue',  // Not supported in Drupal.
  );

  /**
   * List of supported languages by OHT.
   *
   * @var array
   */
  protected $supportedRemoteLanguages = array();

  /**
   * Constructs a OhtTranslator object.
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
    return new static(
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->setTranslator($job->getTranslator());

    try {
      $source_language = $job->getRemoteSourceLanguage();
      $target_language = $job->getRemoteTargetLanguage();
      $notes = $job->getSetting('notes');
      $expertise = $job->getSetting('expertise');

      $resource_uuids = $this->getResourceUuids($job);

      /** @var JobItemInterface $job_item */
      foreach ($job->getItems() as $job_item) {
        $job_item_id = $job_item->id();
        $resource_uuid = $resource_uuids[$job_item_id];
        $result = $this->newTranslationProject($job_item_id, $source_language, $target_language, $resource_uuid, $notes, $expertise);
        $job_item->addRemoteMapping(NULL, $result['project_id'], array(
          'remote_identifier_2' => $resource_uuid,
          'word_count' => $result['wordcount'],
          'remote_data' => array(
            'credits' => $result['credits'],
          ),
        ));
        $job_item->addMessage('OHT Project ID %project_id created. @credits credits reduced from your account.', array(
          '%project_id' => $result['project_id'],
          '@credits' => $result['credits'],
        ));
      }

      $job->submitted('Job has been successfully submitted for translation.');
    }
    catch (TMGMTException $e) {
      \Drupal::logger('tmgmt_oht')->error('Job has been rejected with following error: @error',
        array('@error' => $e->getMessage()));
      $job->rejected('Job has been rejected with following error: @error',
        array('@error' => $e->getMessage()), 'error');
    }
  }

  /**
   * Does a request to OHT services.
   *
   * @param string $path
   *   Resource path.
   * @param string $method
   *   HTTP method (GET, POST...)
   * @param array $params
   *   Form parameters to send to OHT service.
   * @param bool $download
   *   If we expect resource to be downloaded.
   * @param string $content_type
   *   (optional) Content-type to use.
   *
   * @return array
   *   Response array from OHT.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  protected function request($path, $method = 'GET', $params = array(), $download = FALSE, $content_type = 'application/x-www-form-urlencoded') {
    $options = array();
    if (!$this->translator) {
      throw new TMGMTException('There is no Translation entity. Access to public/secret keys is not possible.');
    }

    if (\Drupal::config('tmgmt_oht.settings')->get('use_mock_service')) {
      $request_context = \Drupal::service('router.request_context');
      $url = $request_context->getCompleteBaseUrl() . '/tmgmt_oht_mock' . '/' . self::API_VERSION . '/' . $path;
    }
    elseif ($this->translator->getSetting('use_sandbox')) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/' . $path;
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/' . $path;
    }

    try {
      if ($method == 'GET') {
        // Add query parameters into options.
        $params += [
          'public_key' => $this->translator->getSetting('api_public_key'),
          'secret_key' => $this->translator->getSetting('api_secret_key'),
        ];
        $options['query'] = $params;
      }
      elseif ($method == 'POST') {
        $options = $params;
      }
      $response = $this->client->request($method, $url, $options);

      if ($this->debug) {
        \Drupal::logger('tmgmt_oht')->info("Sending request to OHT at @url method @method with data @data\n\nResponse: @response", array(
          '@url' => $url,
          '@method' => $method,
          '@data' => var_export($options, TRUE),
          '@response' => var_export($response, TRUE),
        ));
      }
    } catch (BadResponseException $e) {
      $response = $e->getResponse();
      throw new TMGMTException('Unable to connect to OHT service due to following error: @error', ['@error' => $response->getReasonPhrase()], $response->getStatusCode());
    }

    if ($response->getStatusCode() != 200) {
      throw new TMGMTException('Unable to connect to the OHT service due to following error: @error at @url',
        array('@error' => $response->getStatusCode(), '@url' => $url));
    }

    // If we are expecting a download, just return received data.
    $received_data = $response->getBody()->getContents();
    if ($download) {
      return $received_data;
    }
    $received_data = json_decode($received_data, TRUE);

    if ($received_data['status']['code'] != 0) {
      throw new TMGMTException('OHT service returned validation error: #%code %error',
        array(
          '%code' => $received_data['status']['code'],
          '%error' => $received_data['status']['msg'],
        ));
    }

    if (!empty($received_data['errors'])) {
      \Drupal::logger('tmgmt_oht')
        ->notice('OHT error: @error', array('@error' => implode('; ', $received_data['errors'])));
      throw new TMGMTException('OHT service returned following error: %error',
        array('%error' => $received_data['status']['msg']));
    }

    return $received_data['results'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return $this->ohtLanguagesMapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $results = array();
    $language_pairs = $translator->getSupportedLanguagePairs();
    foreach ($language_pairs as $language_pair) {
      if ($source_language == $translator->mapToRemoteLanguage($language_pair['source_language'])) {
        $target_language = $language_pair['target_language'];
        $results[$target_language] = $target_language;
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    if (!empty($this->supportedRemoteLanguages)) {
      return $this->supportedRemoteLanguages;
    }

    try {
      $this->setTranslator($translator);
      $supported_languages = $this->request('discover/languages', 'GET', array(), TRUE);
      $result = json_decode($supported_languages, TRUE);

      // Parse languages.
      if (isset($result['results'])) {
        foreach ($result['results'] as $language) {
          $this->supportedRemoteLanguages[$language['code']] = $language['code'];
        }
      }

      // In case of failed request or parsing, we are returning a list of
      // supported remote languages from default OHT mapping.
      if (empty($this->supportedRemoteLanguages)) {
        return array_combine(array_values($this->ohtLanguagesMapping), array_values($this->ohtLanguagesMapping));
      }
    }
    catch (\Exception $e) {
      return array();
    }
    return $this->supportedRemoteLanguages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    $language_pairs = array();

    try {
      $this->setTranslator($translator);
      $supported_language_pairs = $this->request('discover/language_pairs', 'GET', array(), TRUE);
      $result = json_decode($supported_language_pairs, TRUE);

      // Build a mapping of source and target language pairs.
      foreach ($result['results'] as $language) {
        foreach ($language['targets'] as $target_language) {
          $language_pairs[] = array('source_language' => $language['source']['code'], 'target_language' => $target_language['code']);
        }
      }
    } catch (\Exception $e) {
      return array();
    }

    return $language_pairs;
  }

  /**
   * Returns OHT resource uuids.
   *
   * Returns cached OHT resource uuids if available. Otherwise, it creates new
   * file resources, cache and return received OHT resource uuids.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job containing job items used to get resource uuids.
   *
   * @return string[]
   *   A list of resource uuids keyed by job item id.
   */
  public function getResourceUuids(JobInterface $job) {
    $cache = \Drupal::cache('data');
    /** @var \Drupal\tmgmt_file\Format\FormatInterface $xliff_converter */
    $xliff_converter = \Drupal::service('plugin.manager.tmgmt_file.format')->createInstance('xlf');
    $resource_uuids = array();

    foreach ($job->getItems() as $job_item) {
      $job_item_id = $job_item->id();
      $target_language = $job->getRemoteTargetLanguage();
      $cid = "tmgmt_oht:resource_id:$job_item_id:$target_language";
      if ($cached = $cache->get($cid)) {
        $resource_uuid = $cached->data;
      }
      else {
        $conditions = array('tjiid' => array('value' => $job_item_id));
        $xliff = $xliff_converter->export($job, $conditions);
        $name = "JobID_{$job->id()}_JobItemID_{$job_item_id}_{$job->getSourceLangcode()}_{$target_language}";

        $resource_uuid = $this->uploadFileResource($xliff, $name);
        $cache->set($cid, $resource_uuid, Cache::PERMANENT, $job_item->getCacheTags());
      }
      $resource_uuids[$job_item_id] = $resource_uuid;
    }

    return $resource_uuids;
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
  public function getExpertise(JobInterface $job) {
    try {
      $params['source_language'] = $job->getRemoteSourceLanguage();
      $params['target_language'] = $job->getRemoteTargetLanguage();
      $expertise_codes = $this->request('discover/expertise', 'GET', $params);
      $structured_expertise_codes = array();
      foreach ($expertise_codes as $expertise) {
        $structured_expertise_codes[$expertise['code']] = $expertise['name'];
      }
      return $structured_expertise_codes;
    } catch (TMGMTException $e) {
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('api_public_key') && $translator->getSetting('api_secret_key')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
    ]));
  }

  /**
   * Returns quotation.
   *
   * @param JobInterface $job
   *   The job to get quotation for.
   *
   * @return array
   *   List of quotation value.
   */
  public function getQuotation(JobInterface $job) {
    $word_count = $job->getWordCount();
    $source_language = $job->getRemoteSourceLanguage();
    $target_language = $job->getRemoteTargetLanguage();
    $service = 'translation';
    $expertise = $job->getSetting('expertise');

    try {
      $params['resources'] = implode(',', $this->getResourceUuids($job));
      $params['word_count'] = $word_count;
      $params['source_language'] = $source_language;
      $params['target_language'] = $target_language;
      $params['service'] = $service;
      $params['expertise'] = $expertise;
      //$params['proofreading'] = '';
      //$params['currency'] = '';
      return $this->request('tools/quote', 'GET', $params);
    } catch (TMGMTException $e) {
      return array();
    }
  }

  /**
   * Returns the list of account details.
   *
   * @return array
   *   The list of account details.
   */
  public function getAccountDetails() {
    try {
      return $this->request('account');
    } catch (TMGMTException $e) {
      return array();
    }
  }

  /**
   * Creates a text resource at OHT.
   *
   * @param string $text
   *   Text to be translated.
   *
   * @return array
   *   OHT uuid of the resource.
   */
  public function uploadTextResource($text) {
    $params['form_parms']['text'] = $text;
    return $this->request('resources/text', 'POST', $params);
  }

  /**
   * Downloads resource.
   *
   * @param string $oht_uuid
   *
   * @return array
   *   Resource xml.
   */
  public function getResourceDownload($oht_uuid, $project_id = NULL) {
    return $this->request('resources/' . $oht_uuid . '/download', 'GET', ($project_id) ? array('project_id' => $project_id) : array(), TRUE);
  }

  /**
   * Creates new translation project at OHT.
   *
   * @param int $tjiid
   *   Translation job item id.
   * @param string $source_language
   *   Source language.
   * @param string $target_language
   *   Target language.
   * @param string $oht_uuid
   *   OHT uuid.
   * @param string $notes
   *   Notes to be sent with the job.
   * @param string $expertise
   *   Expertise code.
   * @param array $params
   *   Additional params.
   *
   * @return array
   *   OHT project data.
   */
  public function newTranslationProject($tjiid, $source_language, $target_language, $oht_uuid, $notes = NULL, $expertise = NULL, $params = array()) {
    $params += [
      'form_params' => [
        'public_key' => $this->translator->getSetting('api_public_key'),
        'secret_key' => $this->translator->getSetting('api_secret_key'),
        'source_language' => $source_language,
        'target_language' => $target_language,
        'sources' => $oht_uuid,
        'notes' => $notes,
        'callback_url' => Url::fromRoute('tmgmt_oht.callback')->setAbsolute()->toString(),
        'custom0' => $tjiid,
        'custom1' => OhtTranslator::hash($tjiid),
      ],
    ];
    if (!empty($expertise)) {
      $params['form_params']['expertise'] = $expertise;
    }

    return $this->request('projects/translation', 'POST', $params);
  }

  /**
   * Gets OHT project data.
   *
   * @param int $project_id
   *   OHT project id.
   *
   * @return array
   *   Project info.
   */
  public function getProjectDetails($project_id) {
    return $this->request('projects/' . $project_id);
  }

  /**
   * Create new comment to project.
   *
   * @param int $project_id
   * @param string $content (optional)
   *
   * @return array
   *   Response.
   */
  public function addProjectComment($project_id, $content = '') {
    $params = [
      'form_params' => [
        'public_key' => $this->translator->getSetting('api_public_key'),
        'secret_key' => $this->translator->getSetting('api_secret_key'),
        'content' => $content,
      ],
    ];

    return $this->request('projects/' . $project_id . '/comments', 'POST', $params);
  }

  /**
   * Fetch comments by a project id.
   *
   * @param int $project_id
   *   OHT project id.
   *
   * @return array
   *   Project comments.
   */
  public function getProjectComments($project_id) {
    try {
      return $this->request('projects/' . $project_id . '/comments', 'GET');
    }
    catch (\Exception $e) {
      return array();
    }
  }

  /**
   * Gets wordcount.
   *
   * @param string $oht_uuid
   *   OHT resource uuid.
   *
   * @return array
   *   Wordcount info.
   */
  public function getWordcount($oht_uuid) {
    $params['resources'] = $oht_uuid;
    return $this->request('tools/wordcount', 'GET', $params);
  }

  /**
   * Creates a file resource at OHT.
   *
   * @param string $xliff
   *   .XLIFF string to be translated. It is send as a file.
   * @param string $name
   *   File name of the .XLIFF file.
   *
   * @return string
   *   OHT uuid of the resource.
   */
  public function uploadFileResource($xliff, $name) {
    $form_params = [
      'multipart' => [
        [
          'name' => 'public_key',
          'contents' => $this->translator->getSetting('api_public_key'),
        ],
        [
          'name' => 'secret_key',
          'contents' => $this->translator->getSetting('api_secret_key'),
        ],
        [
          'name' => 'upload',
          'contents' => $xliff,
          'filename' => "$name.xliff",
          'headers' => [
            'Content-Type' => 'text/plain',
          ],
        ],
      ],
    ];
    $result = $this->request('resources/file', 'POST', $form_params);

    return reset($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = parent::defaultSettings();
    // Enable CDATA for content encoding in File translator.
    $defaults['xliff_cdata'] = TRUE;
    return $defaults;
  }

  /**
   * Receives and stores a translation returned by OHT.
   *
   * @param array $resource_uuids
   *   The list of resource uuids.
   * @param \Drupal\tmgmt\Entity\JobItem $job_item
   *   The job item to retrieve translation for.
   * @param string $oht_project_id
   *   (optional) The remote OHT project id.
   * @param bool $add_message
   *   (optional) TRUE to add messages to a job item. Otherwise, FALSE.
   */
  public function retrieveTranslation(array $resource_uuids, JobItem $job_item, $oht_project_id = NULL, $add_message = TRUE) {
    try {
      foreach ($resource_uuids as $resource_uuid) {
        $resource = $this->getResourceDownload($resource_uuid, $oht_project_id);
        // On sandbox we get some non existing resource_uuids which do not
        // really exist and return some forbidden error in json format. So we
        // filter these out.
        if (strpos($resource, '<?xml') !== FALSE) {
          if ($data = $this->parseTranslationData($resource)) {
            $job_item->getJob()->addTranslatedData($data);
          }
          else {
            throw new TMGMTException('Could not process received translation data.');
          }

          if ($add_message) {
            if ($job_item->isState(Job::STATE_ACTIVE)) {
              $job_item->addMessage('The translation has been received.');
            }
            else {
              $job_item->addMessage('The translation has been updated.');
            }
          }
        }
        // @todo we should log errors here for the failing resources.
      }
    }
    catch (TMGMTException $e) {
      $job_item->addMessage('Could not get translation from OHT. Message error: @error',
        array('@error' => $e->getMessage()), 'error');
    }
  }

  /**
   * Parses received translation from OHT and returns unflatted data.
   *
   * @param string $data
   *   Base64 encode data, received from OHT.
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
   * @param Job $job
   *   A job containing job items that translations will be fetched for.
   *
   * @return bool
   *   Returns TRUE if there are error messages during the process of retrieving
   *   translations. Otherwise FALSE.
   */
  public function fetchJobs(Job $job) {
    // Search for placeholder item.
    $remotes = RemoteMapping::loadByLocalData($job->id());
    $this->setTranslator($job->getTranslator());
    $translated = 0;
    $not_translated = 0;

    try {
      // Loop over job items and check for if there is a translation available.
      /** @var \Drupal\tmgmt\Entity\RemoteMapping $remote */
      foreach ($remotes as $remote) {
        /** @var JobItem $job_item */
        $job_item = $remote->getJobItem();
        if (empty($remote->getRemoteIdentifier1())) {
          $not_translated++;
          $job_item->addMessage('Could not retrieve project information.', array(), 'error');
          continue;
        }
        $project_details = $this->getProjectDetails($remote->getRemoteIdentifier1());
        // Retrieve translations.
        if (!empty($project_details['resources']['translations'])) {
          $this->retrieveTranslation((array) $project_details['resources']['translations'], $job_item, $project_details['project_id'], FALSE);
          $translated++;
        }
        elseif ($project_details['project_status_code'] == 'cancelled') {
          // Abort the job item.
          $job_item->setState(JobItemInterface::STATE_ABORTED, 'Aborted by One Hour Translation.');
          $job_item->save();
          $not_translated++;
        }
        else {
          $not_translated++;
        }
      }
      if (empty($not_translated)) {
        $job->addMessage('Fetched translations for @translated job items.', array('@translated' => $translated));
      }
      else {
        $job->addMessage('Fetched translations for @translated job items, @not_translated are not translated yet.', array('@translated' => $translated, '@not_translated' => $not_translated));
      }
    } catch (TMGMTException $e) {
      $job->addMessage('Could not pull translation resources.', array(), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    $remotes = RemoteMapping::loadByLocalData($job->id());
    $this->setTranslator($job->getTranslator());

    // Loop over job items and cancel each of them.
    /** @var \Drupal\tmgmt\Entity\RemoteMapping $remote */
    foreach ($remotes as $remote) {
      /** @var JobItem $job_item */
      $job_item = $remote->getJobItem();
      if (!$job_item->isAborted()) {
        try {
          // Cancel the project on One Hour Translation site.
          $this->cancelProject($remote->getRemoteIdentifier1());
          // Abort the job item.
          $job_item->setState(JobItemInterface::STATE_ABORTED, 'Aborted by user.');
        }
        catch (TMGMTException $e) {
          $job_item->addMessage('Abortion failed: @error', array('@error' => $e->getMessage()), 'error');
        }
        $job_item->save();
      }
    }

    // Abort the job in the current system.
    return parent::abortTranslation($job);
  }

  /**
   * Cancels the OHT project.
   *
   * @param int $project_id
   *   OHT project id.
   *
   * @return array
   *   The response after cancelled project.
   */
  public function cancelProject($project_id) {
    $public_key = $this->translator->getSetting('api_public_key');
    $secret_key = $this->translator->getSetting('api_secret_key');
    return $this->request('projects/' . $project_id . '?secret_key=' . $secret_key . '&public_key=' . $public_key, 'DELETE');
  }

  /**
   * Creates a secret hash for Oht reference.
   *
   * @param string $id
   *   The id to hash.
   *
   * @return string
   *   Returns hashed string.
   */
  public static function hash($id) {
    return md5(Settings::getHashSalt() . $id);
  }
}
