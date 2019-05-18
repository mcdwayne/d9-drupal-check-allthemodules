<?php

namespace Drupal\ics_field\Normalizer;

/**
 * Interface UrlNormalizerInterface
 *
 * @package Drupal\ics_field\Normalizer
 */
interface UrlNormalizerInterface {

  /**
   * Normalize a url from a request
   *
   * @param string $url
   * @param string $scheme
   * @param string $schemaAndHttpHost
   *
   * @return mixed
   */
  public function normalize($url, $scheme, $schemaAndHttpHost);

}
