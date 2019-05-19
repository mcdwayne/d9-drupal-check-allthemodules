<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for tmgmt_memory_segment_translation entity.
 *
 * @ingroup tmgmt_memory
 */
interface SegmentTranslationInterface extends ContentEntityInterface {

  /**
   * Return the state of the Segment translation.
   *
   * If a Segment translation is FALSE it will not be used in new translations.
   *
   * @return bool
   *   Return TRUE if the Segment translation is enabled, FALSE otherwise.
   */
  public function getState();

  /**
   * Return the ID of the Source.
   *
   * @return int
   *   The Segment ID.
   */
  public function getSourceId();

  /**
   * Return the source.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface
   *   The Segment entity.
   */
  public function getSource();

  /**
   * Return the ID of the Target.
   *
   * @return string
   *   The Segment ID.
   */
  public function getTargetId();

  /**
   * Return the Target.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface
   *   The Segment entity.
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
   * Enable or disable the Segment translation.
   *
   * If a Segment translation is FALSE it will not be used in new translations.
   *
   * Changing the state of a Segment translation will change the state of all
   * the Usage translations that are related to it.
   *
   * @param bool $state
   *   TRUE to enable it, FALSE to disable.
   */
  public function setState($state);

}
