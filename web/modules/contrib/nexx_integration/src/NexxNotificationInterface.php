<?php

namespace Drupal\nexx_integration;

/**
 * Interface NexxNotificationInterface.
 *
 * @package Drupal\nexx_integration
 */
interface NexxNotificationInterface {

  /**
   * Insert taxonomy terms into omnia CMS.
   *
   * @param string $streamtype
   *   Type of data to be inserted. Allowed values are:
   *        - actor: Actor
   *        - channel: Video channel
   *        - tag: arbitrary tag.
   * @param int $reference_number
   *   Drupal id of the given taxonomy term.
   * @param string $value
   *   Name of taxonomy term.
   */
  public function insert($streamtype, $reference_number, $value);

  /**
   * Update taxonomy term or video reference numbers in omnia CMS.
   *
   * For taxonomy terms, this can update the name, for videos this updates the
   * media id.
   *
   * @param string $streamtype
   *   Type of data to be updated. Allowed values are:
   *        - actor: Actor
   *        - channel: Video channel
   *        - tag: Arbitrary tag
   *        - video: Video.
   * @param int $reference_number
   *   Drupal id of the given taxonomy term, in case of streamtype "video"
   *   this is the reference number of the video inside of Omnia,
   *   not the drupal media ID!
   * @param string $value
   *   Name of taxonomy term, or drupal media id when updating a video.
   */
  public function update($streamtype, $reference_number, $value);

  /**
   * Delete taxonomy terms from omnia CMS.
   *
   * @param string $streamtype
   *   Type of data to be inserted. Allowed values are:
   *        - actor: Actor
   *        - channel: Video channel
   *        - tag: arbitrary tag.
   * @param int $reference_number
   *   Drupal id of the given taxonomy term.
   * @param array $values
   *   Values for the nexx notification.
   */
  public function delete($streamtype, $reference_number, array $values);

}
