<?php

namespace Drupal\entity_comparison\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity comparison entities.
 */
interface EntityComparisonInterface extends ConfigEntityInterface {

  /**
   * Get add link's text
   *
   * @return mixed
   */
  public function getAddLinkText();

  /**
   * Get remove link's text
   *
   * @return mixed
   */
  public function getRemoveLinkText();

  /**
   * Get limit
   *
   * @return mixed
   */
  public function getLimit();

  /**
   * Get selected entity type
   *
   * @return mixed
   */
  public function getTargetEntityType();

  /**
   * Get selected bundle type
   *
   * @return mixed
   */
  public function getTargetBundleType();

  /**
   * Get link array
   *
   * @param $entity_id
   * @return mixed
   */
  public function getLink($entity_id);
}
