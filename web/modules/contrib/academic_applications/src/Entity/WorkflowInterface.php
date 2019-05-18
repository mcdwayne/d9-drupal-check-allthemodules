<?php

namespace Drupal\academic_applications\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining workflow entities.
 */
interface WorkflowInterface extends ConfigEntityInterface {

  /**
   * Gets the application form ID.
   *
   * @return string
   *   The application form ID.
   */
  public function getApplication();

  /**
   * Gets the upload form ID.
   *
   * @return string
   *   The upload form ID.
   */
  public function getUpload();

}
