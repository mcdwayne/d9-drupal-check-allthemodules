<?php

namespace Drupal\account_modal\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart empty event.
 *
 * @see \Drupal\account_modal\Event\AccountModalEvents
 */
class PagesEvent extends Event {

  /**
   * The possible pages supported.
   *
   * @var array[]
   */
  protected $pages;

  /**
   * Constructs a new PagesEvent.
   *
   * @param array[] $pages
   *   The pages array.
   */
  public function __construct(array $pages) {
    $this->pages = $pages;
  }

  /**
   * Gets the pages array.
   *
   * @return array[]
   *   The pages array.
   */
  public function getPages() {
    return $this->pages;
  }

  /**
   * Sets the pages array.
   *
   * @param array[] $pages
   *
   * @return self
   */
  public function setPages(array $pages) {
    $this->pages = $pages;
    return $this;
  }

}
