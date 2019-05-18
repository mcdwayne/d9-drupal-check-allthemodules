<?php

namespace Drupal\aws_cloud\Service;

use Drupal\cloud\Entity\CloudConfig;

/**
 * Interface AwsPricingServiceInterface.
 */
interface AwsPricingServiceInterface {

  /**
   * Set the cloud context.
   *
   * @param string $cloud_context
   *   Cloud context string.
   */
  public function setCloudContext($cloud_context);

  /**
   * Set the cloud configuration entity.
   *
   * @param \Drupal\cloud\Entity\CloudConfig $cloud_config_entity
   *   The cloud config entity.
   */
  public function setCloudConfigEntity(CloudConfig $cloud_config_entity);

  /**
   * Get instance types from the EC2 pricing endpoint.
   *
   * @return array
   *   Instance type array.
   */
  public function getInstanceTypes();

}
