<?php

namespace Drupal\thumbor_effects_crop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\crop\CropInterface;

/**
 * Defines an interface for Thumbor Effects Crop manager.
 */
interface ThumborCropManagerInterface {

  /**
   * Reacts to all entity inserts in order to create a crop when necessary.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The created entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onEntityInsert(EntityInterface $entity): void;

  /**
   * Reacts to all entity updates in order to update a crop when necessary.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The updated entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onEntityUpdate(EntityInterface $entity): void;

  /**
   * Check if a given file has a compatible crop.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file the crop applies to.
   *
   * @return bool
   *   Whether or not a crop exists.
   */
  public static function hasCropForFile(FileInterface $file): bool;

  /**
   * Create a crop entity for the given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   File this crop applies to.
   *
   * @return \Drupal\crop\CropInterface|null
   *   The crop entity.
   */
  public function createCropForFile(FileInterface $file): CropInterface;

  /**
   * Gets the compatible crop entity for the given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   File this crop applies to.
   *
   * @return \Drupal\crop\CropInterface|null
   *   The crop entity.
   */
  public static function getCropForFile(FileInterface $file): CropInterface;

  /**
   * Delete a crop entity.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop entity to delete.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteCrop(CropInterface $crop): void;

  /**
   * Get the aspect ratio from a crop entity.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop entity to get the aspect ratio from.
   *
   * @return null|string
   *   The aspect ratio.
   */
  public static function getAspectRatio(CropInterface $crop): ?string;

  /**
   * Get the orientation of a crop entity.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop entity to get the aspect ratio from.
   *
   * @return string
   *   The orientation.
   */
  public static function getOrientation(CropInterface $crop): string;

}
