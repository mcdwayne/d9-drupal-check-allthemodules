<?php

namespace Drupal\s3\Plugin\AWS\Service;

use Aws\S3\S3Client;
use Drupal\aws\Entity\ProfileInterface;
use Drupal\aws\ServiceInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Something.
 *
 * @AwsService(
 *   id = "s3",
 *   name = @Translation("Simple Storage Service (S3)"),
 *   description = @Translation("S3"),
 * )
 */
class S3 extends PluginBase implements ServiceInterface {

  /**
   * The instantiated S3Client object.
   *
   * @var \Aws\S3\S3Client
   */
  protected $s3;

  /**
   * {@inheritdoc}
   */
  public function loadProfile(ProfileInterface $profile) {
    $this->route53 = new S3Client($profile->getClientArgs());
  }

}
