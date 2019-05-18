<?php

namespace Drupal\cloudfront_purger;

use Aws\CloudFront\CloudFrontClient;
use Psr\Log\LoggerInterface;

/**
 * Provides a cache tags invalidator for CloudFront.
 */
class CloudFrontInvalidator implements CloudFrontInvalidatorInterface {

  /**
   * The CloudFront client.
   *
   * @var \Aws\CloudFront\CloudFrontClient
   */
  protected $client;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * CloudFrontInvalidator constructor.
   *
   * @param \Aws\CloudFront\CloudFrontClient $client
   *   The CloudFront client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(CloudFrontClient $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $paths, $distribution_id) {
    $result = $this->client->createInvalidation([
      'DistributionId' => $distribution_id,
      'InvalidationBatch' => [
        'CallerReference' => time(),
        'Paths' => [
          'Items' => $paths,
          'Quantity' => count($paths),
        ],
      ],
    ]);
    $this->logger->info('Successfully invalidated URLS: @urls', ['@urls' => implode(', ', $paths)]);
    return $result['Invalidation']['Id'];
  }

}
