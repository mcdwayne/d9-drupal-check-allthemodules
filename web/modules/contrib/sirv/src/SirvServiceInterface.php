<?php

namespace Drupal\sirv;

/**
 * Defines an interface for a Sirv service.
 */
interface SirvServiceInterface {

  const API_VERSION = '2006-03-01';
  const REGION = '';
  const USE_PATH_STYLE_ENDPOINT = TRUE;
  const ENDPOINT_SCHEME = 'https';

  /**
   * Configures and returns an S3 client.
   *
   * @param array $config
   *   Configuration from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The configured S3 client.
   */
  public function getS3Client(array $config);

}
