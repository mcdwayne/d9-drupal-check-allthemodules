<?php

namespace Drupal\nexx_integration;

/**
 * Interface VideoManagerServiceInterface.
 *
 * @package Drupal\nexx_integration
 */
interface VideoManagerServiceInterface {

  /**
   * Retrieve video data field name.
   *
   * @return string
   *   The name of the field.
   *
   * @throws \Exception
   */
  public function videoFieldName();

  /**
   * Get the entity type of videos.
   *
   * @return string
   *   The entity type Id of video entities
   */
  public function entityType();

  /**
   * Get the defined video bundle.
   *
   * @return string
   *   The bundle.
   *
   * @throws \Exception
   */
  public function videoBundle();

}
