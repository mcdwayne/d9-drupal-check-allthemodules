<?php

namespace Drupal\mail_entity_queue\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface;

/**
 * Defines the interface for mail entity queue processors.
 *
 * @see \Drupal\mail_entity_queue\Annotation\MailEntityQueueProcessor
 */
interface MailEntityQueueProcessorInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Sends a queue item and update it with the result.
   *
   * @param \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item
   *   The item to process.
   * @param int $delay
   *   Time in milliseconds to wait after sending the item.
   *
   * @return boolean
   *   Whether the operation was performed successfully.
   */
  public function processItem(MailEntityQueueItemInterface $item, int $delay = 0);

  /**
   * Process items in a queue, based on the queue cadence.
   *
   * @param string $mail_entity_queue
   *   Id of the mail queue to process.
   */
  public function processQueue(string $mail_entity_queue);

}
