<?php

namespace Drupal\cision_notify_pull\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CisionEvent.
 *
 * @package Drupal\Event\Event
 */
class CisionEvent extends Event {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Node {@inheritdoc}.
   */
  public function __construct(EntityInterface $node) {
    $this->node = $node;
  }

  /**
   * Return the node.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   EntityInterface {@inheritdoc}.
   */
  public function getNode() {
    return $this->node;
  }

}
