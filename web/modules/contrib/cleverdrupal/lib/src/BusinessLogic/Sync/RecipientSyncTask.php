<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\DTO\RecipientDTO;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException;

/**
 *
 */
class RecipientSyncTask extends BaseSyncTask {
  const INITIAL_PROGRESS_PERCENT = 5;
  /**
   * @var \CleverReach\BusinessLogic\Entity\Recipients
   */
  private $recipientsSyncService;
  /**
   * @var int
   */
  private $batchSize;
  /**
   * @var array
   */
  private $allRecipientsIdsForSync;
  /**
   * @var \CleverReach\BusinessLogic\Entity\TagCollection
   */
  private $tagsToDelete;
  /**
   * @var bool
   */
  private $includeOrders;
  /**
   * @var int
   */
  private $numberOfRecipientsForSync;
  /**
   * @var float
   */
  private $currentSyncProgress;

  /**
   * RecipientSyncTask constructor.
   *
   * @param array $recipientIds
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagsToDelete
   * @param bool $includeOrders
   */
  public function __construct(array $recipientIds, $tagsToDelete = NULL, $includeOrders = FALSE) {
    $this->batchSize = $this->getConfigService()->getRecipientsSynchronizationBatchSize();
    $this->allRecipientsIdsForSync = $recipientIds;
    $this->numberOfRecipientsForSync = count($recipientIds);
    $this->tagsToDelete = $tagsToDelete ? : new TagCollection();
    $this->includeOrders = $includeOrders;
    $this->currentSyncProgress = self::INITIAL_PROGRESS_PERCENT;
  }

  /**
   *
   */
  public function getNumberOfRecipientsForSync() {
    return $this->numberOfRecipientsForSync;
  }

  /**
   *
   */
  public function getRecipientsIdsForSync() {
    return $this->allRecipientsIdsForSync;
  }

  /**
   *
   */
  public function getCurrentSyncProgress() {
    return $this->currentSyncProgress;
  }

  /**
   *
   */
  public function getBatchSize() {
    return $this->batchSize;
  }

  /**
   *
   */
  public function getIncludeOrders() {
    return $this->includeOrders;
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function getTagsToDelete() {
    return clone $this->tagsToDelete;
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize(
        [
          $this->batchSize,
          $this->allRecipientsIdsForSync,
          $this->numberOfRecipientsForSync,
          $this->tagsToDelete,
          $this->includeOrders,
          $this->currentSyncProgress,
        ]
    );
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    list(
        $this->batchSize,
        $this->allRecipientsIdsForSync,
        $this->numberOfRecipientsForSync,
        $this->tagsToDelete,
        $this->includeOrders,
        $this->currentSyncProgress,
    ) = unserialize($serialized);
  }

  /**
   * Runs task logic.
   *
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
   */
  public function execute() {
    /** @var \CleverReach\BusinessLogic\Entity\TagCollection $allTags */
    $allTags = $this->getRecipientSyncService()->getAllTags();
    $this->reportProgress($this->currentSyncProgress);
    $this->reportProgressWhenNoRecipientIds();

    while (count($this->allRecipientsIdsForSync) > 0) {
      /** @var array $recipientsWithTagsPerBatch */
      $batchIds = $this->getBatchRecipientsIds();
      $recipientsWithTagsPerBatch = $this->getBatchRecipientsWithTagsFromSourceSystem($batchIds);
      $this->reportAlive();
      $recipientDTOs = $this->makeRecipientDTOs($recipientsWithTagsPerBatch, $allTags);
      try {
        $this->getProxy()->recipientsMassUpdate($recipientDTOs);

        // Inform service that batch sync is finished.
        $this->getRecipientSyncService()->recipientSyncCompleted($batchIds);

        // If mass update is successful recipients in batch should be removed
        // from the recipients for sending. State of task is updated.
        $this->removeFinishedBatchRecipientsFromRecipientsForSync();

        // If upload is successful progress should be reported for that batch.
        $this->reportProgressForBatch();
      }
      catch (HttpBatchSizeTooBigException $e) {
        // If HttpBatchSizeTooBigException happens process should be continued with smaller calculated batch.
        $this->reconfigure();
      }
    }

    $this->reportProgress(100);
  }

  /**
   *
   */
  private function reportProgressWhenNoRecipientIds() {
    if (count($this->allRecipientsIdsForSync) === 0) {
      $this->currentSyncProgress = 100;
      $this->reportProgress($this->currentSyncProgress);
    }
  }

  /**
   * @param array $recipientsWithTags
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $allTags
   *
   * @return array
   */
  private function makeRecipientDTOs(array $recipientsWithTags, $allTags) {
    $recipientDTOs = [];

    /** @var \CleverReach\BusinessLogic\Entity\Recipient $recipient */
    foreach ($recipientsWithTags as &$recipient) {
      $recipient->getTags()->remove($this->tagsToDelete);
      $recipientDTOs[] = new RecipientDTO(
        $recipient,
        $allTags->diff($recipient->getTags())->merge($this->tagsToDelete),
        $this->includeOrders,
        // Send activated time always.
        TRUE,
        // Never send deactivated timestamp, integrations should deactivate recipients only by setting
        // activated to 0. Deactivated timestamp should be left for recipients deactivation on CleverReach
        // system. Once recipient is deactivated with deactivated timestamp integrations can't reactivate them!
        FALSE
      );
    }

    return $recipientDTOs;
  }

  /**
   * Gets recipients with tags for provided batch.
   *
   * @param array $batchIDs
   *   Recipient IDs for current batch.
   *
   * @return array
   *
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
   */
  private function getBatchRecipientsWithTagsFromSourceSystem($batchIDs) {
    return $this->getRecipientSyncService()->getRecipientsWithTags($batchIDs, $this->includeOrders);
  }

  /**
   * @return array
   */
  private function getBatchRecipientsIds() {
    return array_slice($this->allRecipientsIdsForSync, 0, $this->batchSize);
  }

  /**
   *
   */
  private function removeFinishedBatchRecipientsFromRecipientsForSync() {
    $this->allRecipientsIdsForSync = array_slice($this->allRecipientsIdsForSync, $this->batchSize);
  }

  /**
   *
   */
  public function canBeReconfigured() {
    return $this->batchSize > 1;
  }

  /**
   * Reduces batch size.
   *
   * @throws HttpUnhandledException
   */
  public function reconfigure() {
    if ($this->batchSize >= 100) {
      $this->batchSize -= 50;
    }
    else {
      if ($this->batchSize > 10 && $this->batchSize < 100) {
        $this->batchSize -= 10;
      }
      else {
        if ($this->batchSize > 1 && $this->batchSize <= 10) {
          --$this->batchSize;
        }
        else {
          throw new HttpUnhandledException('Batch size can not be smaller than 1');
        }
      }
    }

    $this->getConfigService()->setRecipientsSynchronizationBatchSize($this->batchSize);
  }

  /**
   *
   */
  private function reportProgressForBatch() {
    $numberSynchronizedRecipients = $this->numberOfRecipientsForSync - count($this->allRecipientsIdsForSync);

    $progressStep = $numberSynchronizedRecipients *
            (100 - self::INITIAL_PROGRESS_PERCENT) / $this->numberOfRecipientsForSync;

    $this->currentSyncProgress = self::INITIAL_PROGRESS_PERCENT + $progressStep;

    $this->reportProgress($this->currentSyncProgress);
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\Recipients
   */
  private function getRecipientSyncService() {
    if ($this->recipientsSyncService === NULL) {
      $this->recipientsSyncService = ServiceRegister::getService(Recipients::CLASS_NAME);
    }

    return $this->recipientsSyncService;
  }

}
