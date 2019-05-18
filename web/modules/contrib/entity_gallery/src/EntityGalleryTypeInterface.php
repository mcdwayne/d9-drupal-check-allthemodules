<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an entity gallery type entity.
 */
interface EntityGalleryTypeInterface extends ConfigEntityInterface {

  /**
   * Determines whether the entity gallery type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Gets whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function isNewRevision();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision_
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Gets whether 'Submitted by' information should be shown.
   *
   * @return bool
   *   TRUE if the submitted by information should be shown.
   */
  public function displaySubmitted();

  /**
   * Sets whether 'Submitted by' information should be shown.
   *
   * @param bool $display_submitted
   *   TRUE if the submitted by information should be shown.
   */
  public function setDisplaySubmitted($display_submitted);

  /**
   * Gets the preview mode.
   *
   * @return int
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function getPreviewMode();

  /**
   * Sets the preview mode.
   *
   * @param int $preview_mode
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function setPreviewMode($preview_mode);

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this entity gallery type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this entity gallery type.
   */
  public function getDescription();

  /**
   * Gets the gallery type.
   *
   * @return string
   *   The entity type referenced by this entity gallery type.
   */
  public function getGalleryType();

  /**
   * Gets the gallery type bundles.
   *
   * @return string
   *   The entity type bundles referenced by this entity gallery type.
   */
  public function getGalleryTypeBundles();
}
