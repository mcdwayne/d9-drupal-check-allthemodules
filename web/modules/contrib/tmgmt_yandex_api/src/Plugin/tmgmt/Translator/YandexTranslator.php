<?php

/**
 * @file
 * Contains \Drupal\tmgmt_yandex_api\Plugin\tmgmt\Translator\YandexTranslator.
 */

namespace Drupal\tmgmt_yandex_api\Plugin\tmgmt\Translator;

use Drupal\Component\Serialization\Json;
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
 * Yandex translator plugin.
 *
 * Check @link https://tech.yandex.com/translate/ Yandex
 * Translator @endlink. Note that we are using HTTP API.
 *
 * @TranslatorPlugin(
 *   id = "yandex",
 *   label = @Translation("Yandex"),
 *   description = @Translation("Yandex Translator service."),
 *   ui = "Drupal\tmgmt_yandex_api\YandexTranslatorUi"
 * )
 */
class YandexTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

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
    if ($translator->getSetting('api_key')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no();
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    foreach (\Drupal::service('tmgmt.data')
               ->filterTranslatable($job->getData()) as $value) {
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
    $languages = [];
    // Prevent access if the translator isn't configured yet.
    if (!$translator->getSetting('api_key')) {
      // @todo should be implemented by an Exception.
      return $languages;
    }

    try {
      $query = ['ui' => 'en'];
      $request = $this->doRequest($translator, '/api/v1.5/tr.json/getLangs', $query);
      if ($request) {
        $result = Json::decode($request->getBody()->getContents());
        foreach ($result['langs'] as $lang => $lang_name) {
          $languages[$lang] = $lang_name;
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
      'zh-hans' => 'zh',
      'zh-hant' => 'zh',
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
   * Execute a request against the Yandex API.
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
  protected function doRequest(Translator $translator, $path, array $query = [], array $headers = []) {

    $api_key = $translator->getSetting('api_key');
    $query['key'] = $api_key;

    $request_url = "https://translate.yandex.net" . $path;
    $request = new Request('GET', $request_url, $headers);

    $response = $this->client->send($request, ['query' => $query]);
    return $response;
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
      $data = \Drupal::service('tmgmt.data')
        ->filterTranslatable($job_item->getData());
      $translation = array();
      foreach ($data as $key => $value) {
        // Query the translator API.
        try {

          $query = [
            'lang' => $job->getRemoteSourceLanguage() . '-' . $job->getRemoteTargetLanguage(),
            'format' => 'plain', // plain Or html
            'text' => $value['#text'],
          ];
          $request = $this->doRequest($job->getTranslator(), '/api/v1.5/tr.json/translate', $query);
          if ($request) {
            $result = Json::decode($request->getBody()->getContents());

            $results_array = $result['text'];
            $results_text = implode("\n", $results_array);

            $translation[$key]['#text'] = $results_text;

            // Save the translated data through the job.
            $job_item->addTranslatedData(\Drupal::service('tmgmt.data')
              ->unflatten($translation));

          }


        }
        catch (RequestException $e) {
          $job->rejected('Rejected by Yandex Translator: @error', array(
            '@error' => $e->getResponse()
              ->getBody()
              ->getContents()
          ), 'error');
        }
      }
    }
  }

}
