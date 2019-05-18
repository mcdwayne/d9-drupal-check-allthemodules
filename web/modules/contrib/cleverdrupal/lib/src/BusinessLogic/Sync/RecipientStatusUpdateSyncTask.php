<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 *
 */
abstract class RecipientStatusUpdateSyncTask extends BaseSyncTask {
  /**
   * @var array
   */
  public $recipientEmails;

  /**
   * @param array $recipientEmails
   */
  public function __construct(array $recipientEmails) {
    $this->recipientEmails = $recipientEmails;
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize($this->recipientEmails);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $this->recipientEmails = unserialize($serialized);
  }

}
