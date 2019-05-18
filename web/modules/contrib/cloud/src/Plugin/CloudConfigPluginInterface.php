<?php

namespace Drupal\cloud\Plugin;

/**
 * Common interfaces for cloud config plugins.
 *
 * @package Drupal\cloud\Plugin
 */
interface CloudConfigPluginInterface {

  /**
   * Load all config entities.
   *
   * @return array
   *   An array of cloud_config entities
   */
  public function loadConfigEntities();

  /**
   * Load a single cloud_config entity.
   *
   * @param string $cloud_context
   *   The cloud_context to load the entity from.
   *
   * @return mixed
   *   The cloud_config entity.
   */
  public function loadConfigEntity($cloud_context);

  /**
   * Load credentials for a given cloud context.
   *
   * @param string $cloud_context
   *   The cloud_context to load the credentials from.
   *
   * @return mixed
   *   Array of credentials.
   */
  public function loadCredentials($cloud_context);

  /**
   * Return the name of the aws_cloud_instance collection template name.
   *
   * @return string
   *   The instance collection template name.
   */
  public function getInstanceCollectionTemplateName();

  /**
   * Return the name of the aws_cloud_instance collection template name.
   *
   * @return string
   *   The instance collection template name.
   */
  public function getPricingPageRoute();

}
