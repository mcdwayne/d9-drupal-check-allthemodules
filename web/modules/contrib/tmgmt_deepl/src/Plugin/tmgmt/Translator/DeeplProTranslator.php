<?php

namespace Drupal\tmgmt_deepl\Plugin\tmgmt\Translator;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DeepL Pro translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "deepl_pro",
 *   label = @Translation("DeepL Pro"),
 *   description = @Translation("DeepL Pro Translator service."),
 *   ui = "Drupal\tmgmt_deepl\DeeplProTranslatorUi",
 *   logo = "icons/deepl.svg",
 * )
 */
class DeeplProTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

  /**
   * Translation service URL.
   *
   * @var string
   */
  protected $translatorUrl = 'https://api.deepl.com/v2/translate';

  /**
   * Translation usage service URL.
   *
   * @var string
   */
  protected $translatorUsageUrl = 'https://api.deepl.com/v2/usage';

  /**
   * Name of parameter that contains source string to be translated.
   *
   * @var string
   */
  protected $qParamName = 'text';

  /**
   * Max number of text queries for translation sent in one request.
   *
   * @var int
   */
  protected $qChunkSize = 50;

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs a LocalActionBase object.
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
   * Overrides TMGMTDefaultTranslatorPluginController::checkAvailable().
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('auth_key')) {
      return AvailableResult::yes();
    }

    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
    ]));
  }

  /**
   * Implements TMGMTTranslatorPluginControllerInterface::requestTranslation().
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('The translation job has been submitted.');
    }
  }

  /**
   * Helper method to do translation request.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   TMGMT Job to be used for translation.
   * @param array|string $text
   *   Text/texts to be translated.
   *
   * @return array
   *   Userialized JSON containing translated texts.
   */
  protected function deeplProRequestTranslation(Job $job, $text) {
    $translator = $job->getTranslator();

    // Build query params.
    $query_params = [
      'source_lang' => $job->getRemoteSourceLanguage(),
      'target_lang' => $job->getRemoteTargetLanguage(),
      $this->qParamName => $text,
    ];
    return $this->doRequest($translator, $query_params);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return [
      'en' => 'EN',
      'de' => 'DE',
      'fr' => 'FR',
      'es' => 'ES',
      'it' => 'IT',
      'nl' => 'NL',
      'pl' => 'PL',
      'pt-pt' => 'PT',
      'ru' => 'RU',
    ];
  }

  /**
   * Overrides getSupportedRemoteLanguages().
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    // Pre-defined array of available languages.
    return [
      "EN" => $this->t('English'),
      "DE" => $this->t('German'),
      "FR" => $this->t('French'),
      "ES" => $this->t('Spanish'),
      "IT" => $this->t('Italian'),
      "NL" => $this->t('Dutch'),
      "PL" => $this->t('Polish'),
      "PT" => $this->t('Portuguese'),
      "RU" => $this->t('Russian'),
    ];
  }

  /**
   * Overrides getSupportedTargetLanguages().
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {

    $languages = $this->getSupportedRemoteLanguages($translator);

    // There are no language pairs, any supported language can be translated
    // into the others. If the source language is part of the languages,
    // then return them all, just remove the source language.
    if (array_key_exists($source_language, $languages)) {
      unset($languages[$source_language]);
      return $languages;
    }

    return [];
  }

  /**
   * Overrides TMGMTDefaultTranslatorPluginController::hasCheckoutSettings().
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return FALSE;
  }

  /**
   * Local method to do request to DeepL Pro Translate service.
   *
   * @param \Drupal\tmgmt\Entity\Translator $translator
   *   The translator entity to get the settings from.
   * @param array $query_params
   *   (Optional) Additional query params to be passed into the request.
   * @param array $options
   *   (Optional) Additional options that will passed to drupal_http_request().
   *
   * @return array|object
   *   Unserialized JSON response from DeepL Pro.
   *
   * @throws TMGMTException
   *   - Unable to connect to the DeepL Pro Service
   *   - Error returned by the DeepL Pro Service.
   */
  protected function doRequest(Translator $translator, array $query_params = [], array $options = []) {
    // Get custom URL for testing purposes, if available.
    $custom_url = $translator->getSetting('url');
    $url = $custom_url ? $custom_url : $this->translatorUrl;

    // Define headers.
    $headers = [
      'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    // Build the query.
    $query_string = '';
    $query_string .= '&auth_key=' . $translator->getSetting('auth_key');

    // Add text to be translated.
    if (isset($query_params[$this->qParamName])) {
      foreach ($query_params[$this->qParamName] as $source_text) {
        $query_string .= '&text=' . urlencode($source_text);
      }
    }
    // Add source language.
    if (isset($query_params['source_lang'])) {
      $query_string .= '&source_lang=' . $query_params['source_lang'];
    }

    // Add target language.
    if (isset($query_params['target_lang'])) {
      $query_string .= '&target_lang=' . $query_params['target_lang'];
    }

    // Add additional settings.
    if (!empty($translator->getSetting('tag_handling'))) {
      $query_string .= '&tag_handling=' . urlencode($translator->getSetting('tag_handling'));
    }

    if (!empty($translator->getSetting('non_splitting_tags'))) {
      $query_string .= '&non_splitting_tags=' . urlencode($translator->getSetting('non_splitting_tags'));
    }

    if (!empty($translator->getSetting('ignore_tags'))) {
      $query_string .= '&ignore_tags=' . urlencode($translator->getSetting('ignore_tags'));
    }

    // Split sentences/ preserve formatting are set as required options.
    $query_string .= '&split_sentences=' . $translator->getSetting('split_sentences');
    $query_string .= '&preserve_formatting=' . $translator->getSetting('preserve_formatting');

    // Build request object.
    $request = new Request('POST', $url, $headers, $query_string);

    // Send the request with the query.
    try {
      $response = $this->client->send($request);
    }
    catch (BadResponseException $e) {
      $error = json_decode($e->getResponse()->getBody(), TRUE);
      throw new TMGMTException('DeepL Pro service returned following error: @error', ['@error' => $error['error']['message']]);
    }

    // Process the JSON result into array.
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Provide translatorUrl for override  in automated testing.
   *
   * @param string $translator_url
   *   Url of translator service.
   */
  final public function setTranslatorUrl($translator_url) {
    $this->translatorUrl = $translator_url;
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();
    foreach ($job_items as $job_item) {
      if ($job->isContinuous()) {
        $job_item->active();
      }
      // Pull the source data array through the job and flatten it.
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($job_item->getData());

      $translation = [];
      $q = [];
      $keys_sequence = [];
      $i = 0;

      // Build DeepL Pro q param and preserve initial array keys.
      foreach ($data as $key => $value) {
        $q[] = $value['#text'];
        $keys_sequence[] = $key;
      }

      try {

        // Split $q into chunks of self::qChunkSize.
        foreach (array_chunk($q, $this->qChunkSize) as $_q) {

          // Get translation from DeepL Pro.
          $result = $this->deeplProRequestTranslation($job, $_q);

          // Collect translated texts with use of initial keys.
          foreach ($result['translations'] as $translated) {
            $translation[$keys_sequence[$i]]['#text'] = Html::decodeEntities($translated['text']);
            $i++;
          }
        }

        // Save the translated data through the job.
        // NOTE that this line of code is reached only in case all translation
        // requests succeeded.
        $job_item->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translation));
      }
      catch (TMGMTException $e) {
        $job->rejected('Translation has been rejected with following error: @error',
          ['@error' => $e->getMessage()], 'error');
      }
    }
  }

  /**
   * Local method to do request to DeepL Pro Usage service.
   *
   * @param \Drupal\tmgmt\Entity\Translator $translator
   *   The translator entity to get the settings from.
   *
   * @return array|object
   *   Unserialized JSON response from DeepL Pro.
   *
   * @throws TMGMTException
   *   - Unable to connect to the DeepL Pro Service
   *   - Error returned by the DeepL Pro Service.
   */
  public function getUsageData(Translator $translator) {
    // Set custom data for testing purposes, if available.
    $custom_usage_url = $translator->getSetting('url_usage');
    $custom_auth_key = $translator->getSetting('auth_key');

    $url = $custom_usage_url ? $custom_usage_url : $this->translatorUsageUrl;
    $auth_key = $custom_auth_key ? $custom_auth_key : $translator->getSetting('auth_key');

    // Prepare Guzzle Object.
    $request = new Request('GET', $url);

    // Build the query.
    $query_string = '&auth_key=' . $auth_key;

    // Send the request with the query.
    try {
      $response = $this->client->send($request, ['query' => $query_string]);
    }
    catch (BadResponseException $e) {
      return json_decode($e->getCode());
    }

    // Process the JSON result into array.
    return json_decode($response->getBody(), TRUE);
  }

}
