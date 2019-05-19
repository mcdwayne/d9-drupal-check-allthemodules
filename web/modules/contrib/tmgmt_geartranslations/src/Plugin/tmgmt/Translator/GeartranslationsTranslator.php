<?php

namespace Drupal\tmgmt_geartranslations\Plugin\tmgmt\Translator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface as Factory;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt_geartranslations\Geartranslations\APIConnector;

/**
 * GearTranslations translator plugin for TMGMT.
 *
 * @TranslatorPlugin(
 *   id = "geartranslations",
 *   label = @Translation("GearTranslations"),
 *   description = @Translation("GearTranslations Translation service for TMGMT."),
 *   ui = "Drupal\tmgmt_geartranslations\GeartranslationsTranslatorUi",
 *   logo = "icons/geartranslations.png"
 * )
 */
class GeartranslationsTranslator extends TranslatorPluginBase implements Factory {
  /**
   * GearTranslations API wrapper
   * @var APIConnector
   */
  private $api;

  /**
   * Local to remote language mappings, in the form of 'local' => 'remote'.
   * @var array
   */
  const DEFAULT_REMOTE_LANGUAGE_MAPPINGS = [
    'bg' => 'bu',
    'en' => 'en-uk',
    'es' => 'es-sp',
    'ml' => 'ms',
    'nb' => 'no',
    'nn' => 'no',
    'pt-br' => 'pt',
    'pt-pt' => 'pt-po',
    'zh-hans' => 'zh-CN',
    'zh-hant' => 'zh-TW'
  ];

	/**
	 * Constructor
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
	 */
	public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($this->prepareAPI($translator)) {
      try {
        $this->api->ping();
        $result = AvailableResult::yes();
      }
      catch (TMGMTException $e) {
        $message = $e->getMessage();
        $humanMessage = '@translator is not configured correctly or the service is down. Please ' .
                        '<a href=:configured>check your credentials</a>.<br>Details : @message';

        $result = AvailableResult::no(t($humanMessage, [
          '@translator' => $translator->label(),
          ':configured' => $translator->url(),
          '@message' => $message
         ]));
      }
    }
    else {
      $humanMessage = '@translator is not configured yet. Please ' .
                      '<a href=:configured>configure</a> the connector first.';

      $result = AvailableResult::no(t($humanMessage, [
        '@translator' =>  $translator->label(),
        ':configured' =>  $translator->url()
      ]));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $translator = $job->getTranslator();

    if ($this->prepareAPI($translator)) {
      try {
        $this->api->requestTranslation($job);
        $job->submitted('The translation job has been submitted to the provider.');
      }
      catch (TMGMTException $e) {
        $job->rejected('Translation has been rejected with following error: @error',
          ['@error' => $e->getMessage()]);
      }
    }
    else {
      $job->rejected('Translation service is not properly configured.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    $translator = $job->getTranslator();

    if ($this->prepareAPI($translator)) {
      try {
        $response = $this->api->abortTranslationRequest($job);

        if ($response['status'] == 'ok') {
          $job->addMessage('The remote translation was successfully aborted.');
          return parent::abortTranslation($job);
        }
        else {
          $job->addMessage('The translation has been already processed and cannot be aborted. ' .
            "We have received your request, and we'll get in contact with you to " .
            'see what can be done.', NULL, 'error');
          return FALSE;
        }
      }
      catch (TMGMTException $e) {
        $job->addMessage('A service error has been found: @error', ['@error' => $e->getMessage()],
          'error');
        return FALSE;
      }
    }

    $job->addMessage('Remote service is not properly accessible');
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return self::DEFAULT_REMOTE_LANGUAGE_MAPPINGS;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $languages = [];

    try {
      if ($this->prepareAPI($translator)) {
        foreach ($this->api->getLanguages() as $language) {
          $languages[$language['code']] = $language['name'];
        }
      }
    }
    catch (TMGMTException $exception) { }

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $languages = [];

    if ($this->prepareAPI($translator)) {
      foreach ($this->api->getTargetLanguages($source_language) as $language) {
        $languages[$language['code']] = $language['name'];
      }
    }

    return $languages;
  }

  /**
   * Instanciate GearTranslations API if needed
   * @param many $translator Translator wrapper.
   */
  private function prepareAPI($translator) {
    if (!APIConnector::isConfigured($translator)) {
      return false;
    }

    if (!isset($this->api)) {
      $this->api = APIConnector::build($translator);
    }

    return true;
  }
}
