<?php

namespace Drupal\cloudfront_purger;

/**
 * Provides an invalidator for CloudFront.
 */
interface CloudFrontInvalidatorInterface {

  /**
   * Invalidates paths for the distribution ID.
   *
   * @param string[] $paths
   *   A list of paths.
   * @param string $distribution_id
   *   The distribution ID.
   *
   * @return string
   *   The invalidation ID.
   *
   * @throws \Aws\Exception\AwsException
   *   If there is an error communicating with the CloudFront API.
   */
  public function invalidate(array $paths, $distribution_id);

}
