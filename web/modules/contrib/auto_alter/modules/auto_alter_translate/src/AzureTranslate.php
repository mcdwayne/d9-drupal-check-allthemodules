<?php

namespace Drupal\auto_alter_translate;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Utility\Xss;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class to connect to Azure Cognitive Service.
 */
class AzureTranslate {

  /**
   * The httpClient.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Class constructor.
   */
  public function __construct(LanguageManagerInterface $language_manager, ClientInterface $http_client, ConfigFactory $configFactory, LoggerChannelFactory $loggerFactory) {
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
    $this->config = $configFactory->get('auto_alter_translate.settings');
    $this->loggerFactory = $loggerFactory->get('auto_alter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('http_client')
    );
  }

  /**
   * Check if setup is complete.
   */
  public function checksetup() {
    $endpoint = Xss::filter($this->config->get('endpoint'));
    $api_key = Xss::filter($this->config->get('api_key'));
    if (empty($api_key) || empty($endpoint)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Get the translation of description.
   */
  public function gettranslation(string $inputStr, $endpoint = FALSE, $api_key = FALSE, $fromLanguage = "en", $toLanguage = FALSE) {
    $client = $this->httpClient;
    if (empty($toLanguage)) {
      $toLanguage = $this->languageManager->getCurrentLanguage()->getId();
    }
    if ($fromLanguage != $toLanguage) {
      try {
        $endpoint = $endpoint ? $endpoint : $this->config->get('endpoint');
        $api_key = $api_key ? $api_key : $this->config->get('api_key');

        $params = "to=" . $toLanguage . "&from=" . $fromLanguage;
        $translateUrl = Xss::filter($endpoint) . "&" . $params;
        $request = $client->post($translateUrl, [
          'headers' => [
            'Ocp-Apim-Subscription-Key' =>  Xss::filter($api_key),
            'Content-Type' => 'application/json',
          ],
          'json' => [['text' => Xss::filter($inputStr)]],
        ]);
      }
      catch (RequestException $e) {
        $this->loggerFactory->error(
          "Azure Cognitive Services Translation error code @code: @message",
          [
            '@code' => $e->getCode(),
            '@message' => $e->getMessage(),
          ]
        );
        if ($e->hasResponse()) {
          $request = $e->getResponse();
        }
        else {
          $request = FALSE;
        }
      }
      return $request;
    }
    else {
      return FALSE;
    }
  }

}
