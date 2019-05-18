<?php

namespace Drupal\og_sm\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the site event.
 *
 * @see \Drupal\og_sm\Event\SiteEvents
 */
class SiteEvent extends Event {

  /**
   * The node type entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * Constructs a site event object.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   */
  public function __construct(NodeInterface $site) {
    $this->site = $site;
  }

  /**
   * Gets the site node.
   *
   * @return \Drupal\node\NodeInterface
   *   The site node.
   */
  public function getSite() {
    return $this->site;
  }

}
