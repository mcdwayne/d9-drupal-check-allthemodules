<?php

namespace Drupal\inherit_link_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Inherit link entities.
 */
interface InheritLinkInterface extends ConfigEntityInterface {

  /**
   * Get element selector attribute.
   *
   * @return string
   *   Element selector.
   */
  public function getElementSelector();

  /**
   * Get link selector attribute.
   *
   * @return string
   *   Link selector.
   */
  public function getLinkSelector();

  /**
   * Get prevent selector attribute.
   *
   * @return string
   *   Prevent selector.
   */
  public function getPreventSelector();

  /**
   * Get hide element attribute.
   *
   * @return bool
   *   Hide element.
   */
  public function getHideElement();

  /**
   * Get auto external attribute.
   *
   * @return bool
   *   Auto external.
   */
  public function getAutoExternal();

}
