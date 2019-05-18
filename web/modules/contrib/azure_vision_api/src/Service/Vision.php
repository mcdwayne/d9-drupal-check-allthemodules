<?php

namespace Drupal\azure_vision_api\Service;

use Drupal\azure_cognitive_services_api\Service\Client as AzureClient;
use Drupal\Core\Config\ConfigFactory;

/**
 *
 * @property \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig config
 */
class Vision {

  /**
   * @var \Drupal\azure_cognitive_services_api\Service\Client
   */
  private $azureClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Constructor for the Vision API class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory, AzureClient $azureClient) {
    $this->config = $configFactory->get('azure_vision_api.settings');
    $this->azureClient = $azureClient;
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1ff.
   *
   * @param $photoUrl
   * @param bool $visualFeatures
   * @param bool $details
   *
   * @return bool|mixed
   */
  public function analyze($photoUrl,
                          $visualFeatures = TRUE,
                          $details = TRUE
  ) {
    $uri = $this->config->get('api_url') . 'analyze';
    $params = [];

    if ($details) {
      $allowedDetails = $this->config->get('allowed_details');
      $params['details'] = implode(',', $allowedDetails);
    }
    if ($visualFeatures) {
      $allowedVisualFeatures = $this->config->get('allowed_visual_features');
      $params['visualFeatures'] = implode(',', $allowedVisualFeatures);
    }

    if (count($params) > 0) {
      $queryString = http_build_query($params);
      $uri = urldecode($uri . '?' . $queryString);
    }

    return self::doRequest($uri, $photoUrl);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1fe.
   *
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  public function describe($photoUrl) {
    $uri = $this->config->get('api_url') . 'describe';
    return self::doRequest($uri, $photoUrl);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1fb.
   *
   * @param $photoUrl
   * @param int $width
   * @param int $height
   * @param bool $smartCropping
   *
   * @return bool|mixed
   */
  public function generateThumbnail($photoUrl,
                                       $width = 100,
                                       $height = 100,
                                       $smartCropping = TRUE
  ) {
    $params = [];
    $params['width'] = $width;
    $params['height'] = $height;
    $params['smartCropping'] = $smartCropping;
    $uri = $this->config->get('api_url') . 'generateThumbnail';

    if (count($params) > 0) {
      $queryString = http_build_query($params);
      $uri = urldecode($uri . '?' . $queryString);
    }
    // TODO Check the return value and handle binary data
    return self::doRequest($uri, $photoUrl);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1fc.
   *
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  public function ocr($photoUrl) {
    $uri = $this->config->get('api_url') . 'ocr';
    return self::doRequest($uri, $photoUrl);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/587f2c6a154055056008f200.
   *
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  public function recognizeText($photoUrl) {
    $uri = $this->config->get('api_url') . 'recognizeText';
    // TODO Check the response value and return the ID
    return self::doRequest($uri, $photoUrl);
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1ff.
   *
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  public function tag($photoUrl) {
    $uri = $this->config->get('api_url') . 'tag';
    return self::doRequest($uri, $photoUrl);
  }

  /**
   * @param $uri
   * @param $photoUrl
   *
   * @return bool|mixed
   */
  private function doRequest($uri, $photoUrl) {
    $body = ['json' => ['url' => $photoUrl]];
    $result = $this->azureClient->doRequest('vision', $uri, 'POST', $body);
    return $result;
  }

}
