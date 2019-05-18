<?php

namespace Drupal\mail_entity_queue\Event;

use Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the mail queue item event.
 *
 * @see \Drupal\mail_entity_queue\Event\MailEntityQueueItemEvents
 */
class MailEntityQueueItemEvent extends Event {

  /**
   * The mail queue item.
   *
   * @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface
   */
  protected $item;

  /**
   * Constructs a new MailEntityQueueItemEvent.
   *
   * @param \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item
   *   The queue item.
   */
  public function __construct(MailEntityQueueItemInterface $item) {
    $this->item = $item;
  }

  /**
   * Gets the queue item.
   *
   * @return \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface
   *   The mail queue item.
   */
  public function getItem() {
    return $this->item;
  }

}
