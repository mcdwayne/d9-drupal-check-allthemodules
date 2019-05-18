<?php

namespace Drupal\rokka;

/**
 * Rokka service interface.
 */
interface RokkaServiceInterface {

  /**
   * Return the given setting from the Rokka module configuration.
   *
   * Examples:
   * - source_image_style (default: , 'rokka_source')
   * - use_hash_as_name (default: true)
   *
   * @param string $param
   *
   * @return mixed
   */
  public function getSettings($param);

  /**
   * @return \Rokka\Client\Image
   */
  public function getRokkaImageClient();

  /**
   * @return \Rokka\Client\User
   */
  public function getRokkaUserClient();

  /**
   * Get an image, given the URI.
   *
   * @param string $uri
   *
   * @return \Drupal\rokka\RokkaAdapter\SourceImageMetadata
   */
  public function loadRokkaMetadataByUri($uri);

  /**
   * Counts the number of images that share the same Hash.
   *
   * @param string $hash
   *
   * @return int
   */
  public function countImagesWithHash($hash);

  /**
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public function getEntityManager();

}
