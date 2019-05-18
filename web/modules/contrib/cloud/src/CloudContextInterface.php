<?php

namespace Drupal\cloud;

/**
 * Defines a common interface for cloud based entities to have cloud context.
 */
interface CloudContextInterface {

  /**
   * Gets the cloud_context from the entity.
   *
   * @return string
   *   Cloud context string.
   */
  public function getCloudContext();

  /**
   * Sets the cloud_context.
   *
   * @param string $cloud_context
   *   Cloud context string.
   */
  public function setCloudContext($cloud_context);

}
