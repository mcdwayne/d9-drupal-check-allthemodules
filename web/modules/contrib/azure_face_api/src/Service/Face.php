<?php

namespace Drupal\azure_face_api\Service;

use Drupal\azure_cognitive_services_api\Service\Client as AzureClient;
use Drupal\Core\Config\ConfigFactory;

/**
 *
 */
class Face {

  /**
   * @var \Drupal\azure_cognitive_services_api\Service\Client
   */
  private $azureClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Constructor for the Face API class.
   */
  public function __construct(ConfigFactory $configFactory, AzureClient $azureClient) {
    $this->config = $configFactory->get('azure_face_api.settings');
    $this->azureClient = $azureClient;
  }

  /**
   * See https://westus.dev.cognitive.microsoft.com/docs/services/563879b61984550e40cbbe8d/operations/563879b61984550f3039523a.
   */
  public function detect($photoUrl,
                         $faceId = TRUE,
                         $faceLandmarks = FALSE,
                         $faceAttributes = TRUE
  ) {
    $uri = $this->config->get('api_url') . 'detect';
    $params = [];

    if ($faceId) {
      $params['returnFaceId'] = TRUE;
    }
    if ($faceLandmarks) {
      $params['returnFaceLandmarks'] = TRUE;
    }
    if ($faceAttributes) {
      $allowedFaceAttributes = $this->config->get('allowed_face_attributes');
      $params['returnFaceAttributes'] = implode(',', $allowedFaceAttributes);
    }

    if (count($params) > 0) {
      $queryString = http_build_query($params);
      $uri = urldecode($uri . '?' . $queryString);
    }

    $body = ['json' => ['url' => $photoUrl]];
    $result = $this->azureClient->doRequest('face', $uri, 'POST', $body);

    return $result;
  }

  /**
   *
   */
  public function findsimilars() {}

  /**
   *
   */
  public function group() {}

  /**
   *
   */
  public function identify() {}

  /**
   *
   */
  public function verify() {}

}
