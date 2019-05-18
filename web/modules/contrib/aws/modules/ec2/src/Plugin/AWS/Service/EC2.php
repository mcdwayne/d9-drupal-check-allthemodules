<?php

namespace Drupal\ec2\Plugin\AWS\Service;

use Aws\Ec2\Ec2Client;
use Drupal\aws\Aws;
use Drupal\aws\Entity\ProfileInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Something.
 *
 * @AwsService(
 *   id = "ec2",
 *   label = @Translation("EC2"),
 *   description = @Translation("Amazon Elastic Compute Cloud (Amazon EC2) is a web service that provides secure, resizable compute capacity in the cloud."),
 * )
 */
class EC2 implements BaseClientInterface, ContainerInjectionInterface {

  /**
   * The instantiated Ec2Client object.
   *
   * @var \Aws\Ec2\Ec2Client
   */
  protected $ec2;

  /**
   * {@inheritdoc}
   */
  public function __construct(Aws $aws) {
    // Load using the default profile.
    $profile = $aws->getProfile('ec2');
    $this->ec2 = new Ec2Client($profile->getClientArgs());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aws')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadProfile(ProfileInterface $profile) {
    $this->ec2 = new Ec2Client($profile->getClientArgs());
  }

}
