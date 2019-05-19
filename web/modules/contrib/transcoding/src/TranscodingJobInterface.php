<?php

namespace Drupal\transcoding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Transcoding job entities.
 *
 * @ingroup transcoding
 */
interface TranscodingJobInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Transcoding job creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transcoding job.
   */
  public function getCreatedTime();

  /**
   * Sets the Transcoding job creation timestamp.
   *
   * @param int $timestamp
   *   The Transcoding job creation timestamp.
   *
   * @return \Drupal\transcoding\TranscodingJobInterface
   *   The called Transcoding job entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * @return \Drupal\transcoding\Plugin\TranscoderPluginInterface
   */
  public function getPlugin();

  /**
   * Process the job through the plugin specified at creation.
   */
  public function process();

  /**
   * Get the service data the plugin previously stored on this job.
   *
   * @return array
   */
  public function getServiceData();

  /**
   * Set the service data.
   *
   * @param $data
   * @return $this
   */
  public function setServiceData($data);

  /**
   * Get the job data the plugin previously stored on this job.
   *
   * @return array
   */
  public function getData();

  /**
   * Set the job data.
   *
   * @param $data
   * @return $this
   */
  public function setData($data);

  /**
   * Get the name of the media's target field.
   *
   * @return string
   */
  public function getMediaTargetFieldName();

}
