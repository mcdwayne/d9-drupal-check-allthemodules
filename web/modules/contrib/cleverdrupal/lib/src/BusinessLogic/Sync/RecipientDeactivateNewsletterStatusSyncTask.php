<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 *
 */
class RecipientDeactivateNewsletterStatusSyncTask extends RecipientStatusUpdateSyncTask {

  /**
   * Runs task logic.
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  public function execute() {
    $this->getProxy()->updateNewsletterStatus($this->recipientEmails);
    $this->reportProgress(100);
  }

}
