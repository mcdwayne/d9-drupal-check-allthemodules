<?php

/**
 * @file
 * Contains \Drupal\tmgmt_microsoft\Plugin\tmgmt\Translator\MicrosoftTranslator.
 */

namespace Drupal\tmgmt_microsoft\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;

/**
 * Microsoft translator plugin.
 *
 * Check @link http://msdn.microsoft.com/en-us/library/dd576287.aspx Microsoft
 * Translator @endlink. Note that we are using HTTP API.
 *
 * @TranslatorPlugin(
 *   id = "microsoft",
 *   label = @Translation("Microsoft"),
 *   description = @Translation("Microsoft Translator service."),
 *   ui = "Drupal\tmgmt_microsoft\MicrosoftTranslatorUi",
 *   logo = "icons/microsoft.svg",
 * )
 */
class MicrosoftTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

  /**
   * Translation service URL.
   *
   * @var string
   */
  protected $translatorUrl = 'http://api.microsofttranslator.com/v2/Http.svc';

  /**
   * Authentication service URL.
   *
   * @var string
   */
  protected $authUrl = 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/';

  /**
   * Maximum supported characters.
   *
   * @var int
   */
  protected $maxCharacters = 10000;

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
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('client_id') && $translator->getSetting('client_secret')) {
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
    foreach (\Drupal::service('tmgmt.data')->filterTranslatable($job->getData()) as $value) {
      // If one of the texts in this job exceeds the max character count
      // the job can't be translated.
      if (Unicode::strlen($value['#text']) > $this->maxCharacters) {
        return TranslatableResult::no(t('The length of the job exceeds tha max character count (@count).', ['@count' => $this->maxCharacters]));
      }
    }
    return parent::checkTranslatable($translator, $job);
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('The translation job has been submitted.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $languages = array();
    // Prevent access if the translator isn't configured yet.
    if (!$translator->getSetting('client_id')) {
      // @todo should be implemented by an Exception.
      return $languages;
    }
    try {
      $request = $this->doRequest($translator, 'GetLanguagesForTranslate');
      if ($request) {
        $dom = new \DOMDocument;
        $dom->loadXML($request->getBody()->getContents());
        foreach ($dom->getElementsByTagName('string') as $item) {
          $languages[$item->nodeValue] = $item->nodeValue;
        }
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(),
        'Cannot get languages from the translator');
      return $languages;
    }

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'zh-hans' => 'zh-CHS',
      'zh-hant' => 'zh-CHT',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $remote_languages = $this->getSupportedRemoteLanguages($translator);

    // There are no language pairs, any supported language can be translated
    // into the others. If the source language is part of the languages,
    // then return them all, just remove the source language.
    if (array_key_exists($source_language, $remote_languages)) {
      unset($remote_languages[$source_language]);
      return $remote_languages;
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return FALSE;
  }

  /**
   * Execute a request against the Microsoft API.
   *
   * @param Translator $translator
   *   The translator entity to get the settings from.
   * @param $path
   *   The path that should be appended to the base uri, e.g. Translate or
   *   GetLanguagesForTranslate.
   * @param $query
   *   (Optional) Array of GET query arguments.
   * @param $headers
   *   (Optional) Array of additional HTTP headers.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function doRequest(Translator $translator, $path, array $query = array(), array $headers = array()) {

    $custom_url = $translator->getSetting('url');
    $url = ($custom_url ? $custom_url : $this->translatorUrl) . '/' . $path;
    $test_token_url = FALSE;
    if ($custom_url) {
      $test_token_url = $custom_url . '/GetToken';
    }
    // The current API uses 2 new parameters and an access token.
    $client_id = $translator->getSetting('client_id');
    $client_secret = $translator->getSetting('client_secret');
    $token = $this->getToken($client_id, $client_secret, $test_token_url);

    $request_url = Url::fromUri($url)->toString();
    $request = new Request('GET', $request_url, $headers);
    $request = $request->withHeader('Authorization', 'Bearer ' . $token);

    $response = $this->client->send($request, ['query' => $query]);
    return $response;
  }

  /**
   * Get the access token.
   *
   * @param $clientID
   *   Application client ID.
   * @param $clientSecret
   *   Application client secret string.
   * @param $test_url
   *   (Optional) The test URL.
   *
   * @return string
   *   The access token.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Thrown when the client id or secret are missing or are not valid.
   */
  protected function getToken($clientID, $clientSecret, $test_url = FALSE) {
    $token = &drupal_static(__FUNCTION__);

    if (isset($token[$clientID][$clientSecret])) {
      return $token[$clientID][$clientSecret];
    }

    if (!$clientID || !$clientSecret) {
      throw new TMGMTException('Missing client ID or secret');
    }

    $url = $this->authUrl;
    if ($test_url) {
      $url = $test_url;
    }

    // Prepare Guzzle Object.
    $post = array(
      'grant_type' => 'client_credentials',
      'scope' => 'http://api.microsofttranslator.com',
      'client_id' => $clientID,
      'client_secret' => $clientSecret,
    );

    try {
      $response = $this->client->request('POST', $url, ['form_params' => $post]);
    }
    catch (BadResponseException $e) {
      $error = json_decode($e->getResponse()->getBody()->getContents(), TRUE);
      throw new TMGMTException('Microsoft Translate service returned the following error: @error', ['@error' => $error['error_description']]);
    }

    $data = json_decode($response->getBody()->getContents(), FALSE);

    if (isset($data->error)) {
      throw new TMGMTException('Failed to acquire token: ' . $data->error_description);
    }
    $token[$clientID][$clientSecret] = $data->access_token;
    return $token[$clientID][$clientSecret];
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();
    /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
    foreach ($job_items as $job_item) {
      if ($job->isContinuous()) {
        $job_item->active();
      }
      // Pull the source data array through the job and flatten it.
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($job_item->getData());
      $translation = array();
      foreach ($data as $key => $value) {
        // Query the translator API.
        try {
          $result = $this->doRequest($job->getTranslator(), 'Translate', array(
            'from' => $job->getRemoteSourceLanguage(),
            'to' => $job->getRemoteTargetLanguage(),
            'contentType' => 'text/plain',
            'text' => $value['#text'],
          ), array(
            'Content-Type' => 'text/plain',
          ));

          // Lets use DOMDocument for now because this service enables us to
          // send an array of translation sources, and we will probably use
          // this soon.
          $dom = new \DOMDocument();
          $dom->loadXML($result->getBody()->getContents());
          $items = $dom->getElementsByTagName('string');
          $translation[$key]['#text'] = $items->item(0)->nodeValue;

          // Save the translated data through the job.
          $job_item->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translation));

        }
        catch (RequestException $e) {
          $job->rejected('Rejected by Microsoft Translator: @error', array('@error' => $e->getResponse()->getBody()->getContents()), 'error');
        }
      }
    }
  }

}
