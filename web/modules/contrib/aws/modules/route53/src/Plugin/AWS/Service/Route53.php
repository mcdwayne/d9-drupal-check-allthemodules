<?php

namespace Drupal\route53\Plugin\AWS\Service;

use Aws\Route53\Route53Client;
use Drupal\aws\Entity\ProfileInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Something.
 *
 * @AwsService(
 *   id = "route_53",
 *   label = @Translation("Route 53"),
 *   description = @Translation("Amazon Route 53 (Route 53) is a scalable and highly available Domain Name System (DNS)."),
 * )
 */
class Route53 extends PluginBase {

  /**
   * The instantiated Ec2Client object.
   *
   * @var \Aws\Ec2\Ec2Client
   */
  protected $route53;

  /**
   * {@inheritdoc}
   */
  public function loadProfile(ProfileInterface $profile) {
    $this->route53 = new Route53Client($profile->getClientArgs());
  }

}
