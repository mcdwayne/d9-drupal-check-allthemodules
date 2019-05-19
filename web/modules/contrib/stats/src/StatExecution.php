<?php

/**
 * @file
 * Contains \Drupal\stats\StatExecution.
 */

namespace Drupal\stats;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\stats\Entity\StatProcessorInterface;

/**
 * Represents the execution of a stat processor for a given trigger entity.
 *
 * @package Drupal\stats
 */
class StatExecution {

  /**
   * @var \Drupal\stats\Entity\StatProcessorInterface
   */
  protected $statProcessor;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $triggerEntity;

  /**
   * StatExecution constructor.
   *
   * @param \Drupal\stats\Entity\StatProcessorInterface $statDefinition
   * @param \Drupal\Core\Entity\ContentEntityInterface $triggerEntity
   */
  public function __construct(StatProcessorInterface $statDefinition, ContentEntityInterface $triggerEntity) {
    $this->statProcessor = $statDefinition;
    $this->triggerEntity = $triggerEntity;
  }

  /**
   * Get associated stat processor.
   *
   * @return \Drupal\stats\Entity\StatProcessorInterface
   */
  public function getStatProcessor(): \Drupal\stats\Entity\StatProcessorInterface {
    return $this->statProcessor;
  }

  /**
   * Get associated trigger entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getTriggerEntity(): \Drupal\Core\Entity\ContentEntityInterface {
    return $this->triggerEntity;
  }

}
