<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for tmgmt_memory_usage_translation entity.
 *
 * @ingroup tmgmt_memory
 */
interface UsageTranslationInterface extends ContentEntityInterface {

  /**
   * Return the state of the Usage translation.
   *
   * If a Usage translation is FALSE it will not be used in new translations.
   *
   * @return bool
   *   Return TRUE if the Usage translation is enabled, FALSE otherwise.
   */
  public function getState();

  /**
   * Return the ID of the Source.
   *
   * @return int
   *   The Usage ID.
   */
  public function getSourceId();

  /**
   * Return the source.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface
   *   The Usage entity.
   */
  public function getSource();

  /**
   * Return the ID of the Target.
   *
   * @return int
   *   The Usage ID.
   */
  public function getTargetId();

  /**
   * Return the Target.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface
   *   The Usage entity.
   */
  public function getTarget();

  /**
   * Return the quality of the translation.
   *
   * @return int
   *   The quality from 0 to 5.
   */
  public function getQuality();

  /**
   * Enable or disable the Usage translation.
   *
   * If a Usage translation is FALSE it will not be used in new translations.
   *
   * @param bool $state
   *   TRUE to enable it, FALSE to disable.
   */
  public function setState($state);

}
