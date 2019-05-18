<?php

namespace Drupal\cloudconvert_media_thumbnail;

use Drupal\media\MediaInterface;

/**
 * Interface MediaThumbnailManagerInterface.
 *
 * @package Drupal\cloudconvert
 */
interface MediaThumbnailManagerInterface {

  /**
   * Queue a task to create a thumbnail for the given Media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   * @param bool $emptyOnly
   *   Queue Media Entity only if no thumbnail is available.
   */
  public function queueThumbnailTask(MediaInterface $media, $emptyOnly = FALSE);

  /**
   * Validate if we want to process it as a conversion.
   *
   * @param string $inputFormat
   *   Input format.
   * @param string $outputFormat
   *   Output format.
   *
   * @return bool
   *   TRUE if image conversion is valid.
   */
  public function validateImageConversion($inputFormat, $outputFormat);

  /**
   * Validate if we want to process it as a thumbnail info process.
   *
   * @param string $inputFormat
   *   Input Format.
   *
   * @return bool
   *   TRUE if valid thumbnail conversion.
   */
  public function validateThumbnailConversion($inputFormat);

  /**
   * Validate if the media source field value is changed.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return bool
   *   TRUE if media source has changed.
   */
  public function mediaSourceHasChanged(MediaInterface $media);

}
