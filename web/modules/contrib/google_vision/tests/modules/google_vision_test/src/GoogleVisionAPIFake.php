<?php

namespace Drupal\google_vision_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\google_vision\GoogleVisionApi;
use GuzzleHttp\ClientInterface;

class GoogleVisionAPIFake extends GoogleVisionApi {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Stores the API key.
   *
   * @var int
   */
  protected $apiKey;


  /**
   * Construct a GoogleVisionAPIFake object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->apiKey = $this->configFactory->get('google_vision.settings')
      ->get('api_key');
  }

  /**
   * Function to retrieve labels for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function labelDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'labelAnnotations' => [
            '0' => [
              'description' => 'This will be filled with Labels.',
            ],
          ],
        ],
      ],
    ];
    return $response;
  }

  /**
   * Function to retrieve image attributes for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function imageAttributesDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'imagePropertiesAnnotation' => [
            'dominantColors' => [
              'colors' => [
                '0' => [
                  'color' => [
                    'red' => 124,
                    'blue' => 159,
                    'green' => 20,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    return $response;
  }

  /**
   * Function to detect landmarks within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function landmarkDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'landmarkAnnotations' => [
            '0' => [
              'description' => 'This will be filled with Landmarks.',
            ],
          ],
        ],
      ],
    ];
    return $response;
  }

  /**
   * Function to detect logos of famous brands within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function logoDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'logoAnnotations' => [
            '0' => [
              'description' => 'This will be filled with Logos.',
            ],
          ],
        ],
      ],
    ];
    return $response;
  }

  /**
   * Function to return the response showing the image contains explicit content.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function safeSearchDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = array(
      'responses' => array(
        '0' => array(
          'safeSearchAnnotation' => array(
            'adult' => 'LIKELY',
            'spoof' => 'VERY_UNLIKELY',
            'medical' => 'POSSIBLE',
            'violence' => 'POSSIBLE'
          ),
        ),
      ),
    );
    return $response;
  }


  /**
   * Function to retrieve texts for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function opticalCharacterRecognition($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'textAnnotations' => [
            '0' => [
              'description' => 'This will be filled with Optical Characters.',
            ],
          ],
        ],
      ],
    ];
    return $response;
  }

  /**
   * Function to fetch faces from a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function faceDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = [
      'responses' => [
        '0' => [
          'faceAnnotations' => [
            '0' => [
              'joyLikelihood' => 'UNLIKELY',
              'sorrowLikelihood' => 'VERY_LIKELY',
              'angerLikelihood' => 'LIKELY',
              'surpriseLikelihood' => 'POSSIBLE',
            ],
          ],
        ],
      ],
    ];
    return $response;
  }
}
