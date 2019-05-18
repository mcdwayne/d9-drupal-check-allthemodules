<?php

namespace Drupal\aws_cloud\Commands\aws;

use Drush\Commands\DrushCommands;
use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;

/**
 * Provides drush commands for instance.
 */
class InstanceCommands extends DrushCommands {

  /**
   * The Aws Ec2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  protected $awsEc2Service;

  /**
   * The Cloud Config Plugin Manager.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * InstanceCommands constructor.
   *
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The Aws Ec2 Service.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The Cloud Config Plugin Manager.
   */
  public function __construct(AwsEc2ServiceInterface $aws_ec2_service, CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    $this->awsEc2Service = $aws_ec2_service;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * Terminates expired instances.
   *
   * @command aws_cloud:terminate_instances
   *
   * @aliases aws-ti
   */
  public function drushInstanceTerminate() {
    $entities = $this->cloudConfigPluginManager->loadConfigEntities('aws_ec2');
    foreach ($entities as $entity) {
      /* @var \Drupal\cloud\Entity\CloudConfig $entity */
      $this->awsEc2Service->setCloudContext($entity->getCloudContext());
      $instances = aws_cloud_get_expired_instances($entity->getCloudContext());
      if ($instances) {
        $this->output()->writeln('Terminating the following instances ' . implode(",", $instances['InstanceIds']));
        $this->awsEc2Service->terminateInstance($instances);
        $this->awsEc2Service->updateInstances();
      }
    }
  }

}
