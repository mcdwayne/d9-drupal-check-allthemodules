<?php

namespace Drupal\trance;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a trance type entity.
 */
interface TranceTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this CmsContent type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this CmsContent type.
   */
  public function getDescription();

  /**
   * Returns whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function shouldCreateNewRevision();

}
