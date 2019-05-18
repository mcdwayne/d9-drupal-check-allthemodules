<?php

/**
 * @file
 * AsfEvent class.
 */

namespace Drupal\asf\Event;

use Symfony\Component\EventDispatcher\Event;

class AsfNodeEvent extends Event {

  protected $node;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity) {
    $this->setNode($entity);
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\node\Entity\Node
   *   The entity of the event.
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * Set the entity of the event.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The entity of the event.
   */
  public function setNode($node) {
    $this->node = $node;
  }

}
