<?php

namespace Drupal\google_cloud_vision_media;

use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;

/**
 * Interface MediaManagerInterface.
 *
 * @package Drupal\google_cloud_vision_media
 */
interface MediaManagerInterface {

  /**
   * Queue the annotation to enrich data for the given Media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   */
  public function queueAnnotation(MediaInterface $media);

  /**
   * Get the Media File.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return \Drupal\file\FileInterface[]
   *   List of File Entities.
   */
  public function getMediaFiles(MediaInterface $media);

  /**
   * Get the File URI of the file to send to Google Vision.
   *
   * @param \Drupal\file\FileInterface $file
   * @param \Drupal\image\ImageStyleInterface|NULL $imageStyle
   *
   * @return string
   */
  public function getFileUri(FileInterface $file, ImageStyleInterface $imageStyle = NULL);

  /**
   * Annotate the files in the media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return \Google\Cloud\Vision\Annotation[]
   *   List of Google Cloud Vision Annotations.
   */
  public function annotate(MediaInterface $media);

  /**
   * Validate if the media source has changed.
   *
   * @param \Drupal\media\MediaInterface $media
   *
   * @return bool
   */
  public function mediaSourceHasChanged(MediaInterface $media);

  /**
   * Validate if the thumbnail is already created.
   *
   * @param \Drupal\file\FileInterface $file
   *   File to send for annotation.
   *
   * @return bool
   *   TRUE if the file is valid to be used.
   */
  public function validateFile(FileInterface $file);

}
