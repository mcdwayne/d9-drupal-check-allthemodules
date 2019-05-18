<?php

namespace Drupal\menu_block_current_language\Event;

use Drupal\Core\Menu\MenuLinkInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class HasTranslationEvent.
 *
 * @package Drupal\menu_block_current_language\Event
 */
class HasTranslationEvent extends Event {

  /**
   * The menu link.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $link;

  /**
   * Determines if menu link is visible.
   *
   * @var bool
   */
  protected $status;

  /**
   * HasTranslationEvent constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link.
   * @param bool $status
   *   The visibility.
   */
  public function __construct(MenuLinkInterface $link, $status) {
    $this->link = $link;
    $this->status = $status;
  }

  /**
   * Determines if link should be visible or not.
   *
   * @return bool
   *   The visibility.
   */
  public function hasTranslation() {
    return $this->status;
  }

  /**
   * Sets the visibility.
   *
   * @param bool $status
   *   The visibility.
   *
   * @return $this
   */
  public function setHasTranslation($status) {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * Gets the menu link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The menu link.
   */
  public function getLink() {
    return $this->link;
  }

  /**
   * Sets the menu link.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   The menu link.
   *
   * @return $this
   */
  public function setLink(MenuLinkInterface $menu_link) {
    $this->link = $menu_link;
    return $this;
  }

}
