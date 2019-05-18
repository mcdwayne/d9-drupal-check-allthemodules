<?php

namespace Drupal\route53;

use Aws\Route53\Route53Client;
use Drupal\aws\Entity\ProfileInterface;
use Drupal\aws\Plugin\AWS\Service\ServiceInterface;

/**
 * Undocumented class.
 */
class Route53 implements ServiceInterface {

  /**
   * The instantiated Route53 object.
   *
   * @var \Aws\Route53\Route53Client
   */
  protected $route53;

  /**
   * Undocumented function.
   */
  public function __construct() {
    $profile = \Drupal::service('aws')->getProfile('aws_route53');
    $this->route53 = new Route53Client($profile->getClientArgs());
  }

  /**
   * {@inheritdoc}
   */
  public function loadProfile(ProfileInterface $profile) {
    $this->route53 = new Route53Client($profile->getClientArgs());
  }

}
