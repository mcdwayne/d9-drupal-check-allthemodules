<?php

namespace Drupal\mail_entity_queue\Event;

final class MailEntityQueueItemEvents {

  /**
   * Name of the event fired after process a mail queue item
   * successfully.
   *
   * @Event
   *
   * @see \Drupal\mail_entity_queue\Event\MailEntityQueueItemEvents
   */
  const MAIL_ENTITY_QUEUE_ITEM_PROCESSED_SUCCESSFULLY = 'mail_entity_queue.mail_entity_queue_item.processed_successfully';

  /**
   * Name of the event fired after process a mail queue item wrongly.
   *
   * @Event
   *
   * @see \Drupal\mail_entity_queue\Event\MailEntityQueueItemEvents
   */
  const MAIL_ENTITY_QUEUE_ITEM_PROCESSED_WRONGLY = 'mail_entity_queue.mail_entity_queue_item.processed_wrongly';

  /**
   * Name of the event fired after check the process status of a mail queue item
   * with a discarded status.
   *
   * @Event
   *
   * @see \Drupal\mail_entity_queue\Event\MailEntityQueueItemEvents
   */
  const MAIL_ENTITY_QUEUE_ITEM_DISCARDED = 'mail_entity_queue.mail_entity_queue_item.discarded';

}
