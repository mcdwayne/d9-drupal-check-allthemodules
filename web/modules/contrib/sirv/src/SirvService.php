<?php

namespace Drupal\sirv;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Aws\S3\S3Client;

/**
 * Defines a Sirv service.
 */
class SirvService implements SirvServiceInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getS3Client(array $config) {
    $client_config = [];

    $client_config['region'] = SirvServiceInterface::REGION;
    $client_config['version'] = SirvServiceInterface::API_VERSION;
    $client_config['use_path_style_endpoint'] = SirvServiceInterface::USE_PATH_STYLE_ENDPOINT;

    $client_config['endpoint'] = SirvServiceInterface::ENDPOINT_SCHEME . '://' . $config['endpoint'];
    $client_config['credentials'] = [
      'key' => $config['access_key'],
      'secret' => $config['secret_key'],
    ];

    // Create the S3Client object.
    $s3Client = new S3Client($client_config);

    return $s3Client;
  }

}
