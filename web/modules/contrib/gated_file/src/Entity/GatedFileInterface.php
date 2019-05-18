<?php

namespace Drupal\gated_file\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Gated file entities.
 *
 * @ingroup gated_file
 */
interface GatedFileInterface {

  /**
   * Gets the Gated entity id().
   *
   * @return string
   *   Name of the Gated file.
   */
  public function getFid();

  /**
   * Sets the Gated file entity id.
   *
   * @param string $id
   *   The Gated file name.
   *
   * @return \Drupal\gated_file\Entity\GatedFileInterface
   *   The called Gated file entity.
   */
  public function setFid($id);

  /**
   * Get the Form Id.
   *
   * @return string
   *   Name of the Gated file.
   */
  public function getFormId();

  /**
   * Set the form id which will be displayed in the field.
   *
   * @param string $form
   *   The id of the form.
   *
   * @return \Drupal\gated_file\Entity\GatedFileInterface
   *   The called Gated file entity.
   */
  public function setForm($form);

}
