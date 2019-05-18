<?php

namespace Drupal\azure_text_analytics_api\Service;

use Drupal\azure_cognitive_services_api\Service\Client as AzureClient;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;

/**
 *
 */
class TextAnalytics {

  /**
   * @var \Drupal\azure_cognitive_services_api\Service\Client
   */
  private $azureClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Constructor for the Text Analytics API class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory, AzureClient $azureClient) {
    $this->config = $configFactory->get('azure_text_analytics_api.settings');
    $this->azureClient = $azureClient;
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/TextAnalytics.V2.0/operations/56f30ceeeda5650db055a3c9.
   *
   * @param $text
   *
   * @return bool|mixed
   */
  public function sentiment($text) {
    $uri = $this->config->get('api_url') . 'sentiment';
    return self::doRequest($uri, $text);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/TextAnalytics.V2.0/operations/56f30ceeeda5650db055a3c6.
   *
   * @param $text
   *
   * @return bool|mixed
   */
  public function keyPhrases($text) {
    $uri = $this->config->get('api_url') . 'keyPhrases';
    return self::doRequest($uri, $text);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/TextAnalytics.V2.0/operations/56f30ceeeda5650db055a3c6.
   *
   * @param $text
   *
   * @return bool|mixed
   */
  public function languages($text) {
    $uri = $this->config->get('api_url') . 'languages';
    return self::doRequest($uri, $text);
  }

  /**
   * @param string|array $text
   *
   * @return string
   */
  private function buildData($text) {
    if (!is_array($text)) {
      $text = [
        1 => [
          'text' => $text,
        ],
      ];
    }
    $data = ['documents' => []];
    foreach ($text as $id => $document) {
      $document['id'] = $id;
      $data['documents'][] = $document;
    }

    return Json::encode($data);
  }

  /**
   * @param $uri
   * @param $text
   *
   * @return bool|mixed
   */
  private function doRequest($uri, $text) {
    $data = self::buildData($text);
    $result = $this->azureClient->doRequest('text_analytics', $uri, 'POST', ['body' => $data]);
    return $result;
  }

}
