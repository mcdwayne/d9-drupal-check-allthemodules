<?php

namespace Drupal\og_sm\Event;

use Drupal\node\NodeTypeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the site type event.
 *
 * @see \Drupal\og_sm\Event\SiteTypeEvents
 */
class SiteTypeEvent extends Event {

  /**
   * The node type entity.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $type;

  /**
   * Constructs a site type event object.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   Configuration object.
   */
  public function __construct(NodeTypeInterface $type) {
    $this->type = $type;
  }

  /**
   * Gets the node type entity.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   The node type entity.
   */
  public function getNodeType() {
    return $this->type;
  }

}
