<?php

namespace Drupal\cloudconvert\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining CloudConvert Task type entities.
 */
interface CloudConvertTaskTypeInterface extends ConfigEntityInterface {

  /**
   * Get the method for finishing the task after it is finished.
   *
   * @return string
   *   Info or download.
   */
  public function getFinishMethod();

  /**
   * Set the method for finishing the task.
   *
   * @param string $finishMethod
   *   Finish method.
   */
  public function setFinishMethod($finishMethod);

}
