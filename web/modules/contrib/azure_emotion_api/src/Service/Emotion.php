<?php

namespace Drupal\azure_emotion_api\Service;

use Drupal\azure_cognitive_services_api\Service\Client as AzureClient;
use Drupal\Core\Config\ConfigFactory;

/**
 *
 */
class Emotion {

  /**
   * @var \Drupal\azure_cognitive_services_api\Service\Client
   */
  private $azureClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Constructor for the Emotion API class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory, AzureClient $azureClient) {
    $this->config = $configFactory->get('azure_emotion_api.settings');
    $this->azureClient = $azureClient;
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/5639d931ca73072154c1ce89/operations/56f23eb019845524ec61c4d7.
   *
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  public function recognize($photoUrl) {
    $uri = $this->config->get('api_url') . 'recognize';
    $body = ['json' => ['url' => $photoUrl]];

    $result = $this->azureClient->doRequest('emotion', $uri, 'POST', $body);
    return $result;
  }

  /**
   *
   */
  public function recognizeEmotionRectangles() {}

  /**
   *
   */
  public function recognizeInVideo() {}

}
