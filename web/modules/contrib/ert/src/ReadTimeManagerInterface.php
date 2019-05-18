<?php

namespace Drupal\ert;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class ReadTimeManager.
 *
 * @package Drupal\ert
 */
interface ReadTimeManagerInterface {
  
  /**
   * @param EntityInterface $entity
   * 
   * @return string
   *    preprocessed read time
   */
  public function getReadTime(EntityInterface $entity);
}