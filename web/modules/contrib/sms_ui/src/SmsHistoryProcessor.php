<?php

namespace Drupal\sms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Processes the SMS UI history queues to delete history records.
 */
class SmsHistoryProcessor {

  /**
   * SMS message history storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsHistoryStorage;

  /**
   * SMS message entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsMessageStorage;

  /**
   * Constructs a new SmsHistoryProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->smsHistoryStorage = $entity_type_manager->getStorage('sms_history');
    $this->smsMessageStorage = $entity_type_manager->getStorage('sms');
  }

  /**
   * Delete messages histories which have been processed and are expired.
   */
  public function cleanUpHistory() {
    $ids = $this->smsHistoryStorage
                ->getQuery()
                ->condition('expiry', time(), '<=')
                ->execute();
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface $sms_history */
    foreach ($this->smsHistoryStorage->loadMultiple($ids) as $sms_history) {
      $sms_history->deleteSmsMessages()
        ->delete();
    }
  }

  /**
   * Remove SMS messages that have no parent history entity.
   */
  public function removeOrphans() {
    // Remove orphaned messages (i.e. messages without history).
    $history = \Drupal::database()->select('sms_history', 'sh')
                      ->fields('sh', ['id']);

    // $children are the SMS messages with valid history items.
    $children = \Drupal::database()->select('sms_history__messages', 'shm')
                      ->fields('shm', ['messages_target_id'])
                      ->condition('entity_id', $history, 'IN');

    // $orphans are SMS messages that are not among the $children.
    $orphans = $this->smsMessageStorage->getQuery()
                      ->condition('id', $children, 'NOT IN')
                      ->execute();
    $this->smsMessageStorage->delete($this->smsMessageStorage->loadMultiple($orphans));
  }

}
