<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\CompositeTask;

/**
 *
 */
class InitialSyncTask extends CompositeTask {

  /**
   *
   */
  public function __construct(array $subTasks = [], $initialProgress = 0) {
    if (empty($subTasks)) {
      $this->setSubscriberListTasks($subTasks);
      $this->setFieldsTasks($subTasks);
      $this->setRecipientSyncTasks($subTasks);
    }

    parent::__construct($subTasks, $initialProgress);
  }

  /**
   * Returns progress by 3 initial sync task groups (keys in returned array): subscriberList, fields and recipients.
   *
   * @return array
   */
  public function getProgressByTask() {
    return [
      'subscriberList' => $this->getSubscriberListTasksProgress(),
      'fields' => $this->getFieldsTasksProgress(),
      'recipients' => $this->getRecipientSyncTasksProgress(),
    ];
  }

  /**
   * Gets count of synchronized recipients.
   *
   * @return int
   */
  public function getSyncedRecipientsCount() {
    /** @var RecipientSyncTask $recipientTask */
    $recipientTask = $this->getSubTask($this->getRecipientSyncTaskName());

    return $recipientTask->getNumberOfRecipientsForSync() - count($recipientTask->getRecipientsIdsForSync());
  }

  /**
   * @inheritdoc
   */
  protected function createSubTask($taskKey) {
    switch ($taskKey) {
      case $this->getAttributesSyncTaskName():
        return $this->makeAttributesSyncTask();

      case $this->getFilterSyncTaskName():
        return $this->makeFilterSyncTask();

      case $this->getGroupSyncTaskName():
        return $this->makeGroupSyncTask();

      case $this->getProductSearchSyncTaskName():
        return $this->makeProductSearchSyncTask();

      case $this->getRecipientSyncTaskName():
        return $this->makeRecipientSyncTask();
    }

    throw new \RuntimeException('Unknown task type! ' . $taskKey);
  }

  /**
   * Sets tasks for first group in initial sync (group sync and product search) to the list of sub tasks.
   *
   * @param array $subTasks
   */
  protected function setSubscriberListTasks(&$subTasks) {
    $subTasks[$this->getGroupSyncTaskName()] = 5;

    if ($this->getConfigService()->isProductSearchEnabled()) {
      $subTasks[$this->getProductSearchSyncTaskName()] = 5;
    }
  }

  /**
   * Gets overall progress of tasks belonging to subscriber list task group.
   *
   * @return float
   */
  protected function getSubscriberListTasksProgress() {
    $result = $this->taskProgressMap[$this->getGroupSyncTaskName()];
    if (isset($this->taskProgressMap[$this->getProductSearchSyncTaskName()])) {
      $result = $result / 2 + $this->taskProgressMap[$this->getProductSearchSyncTaskName()] / 2;
    }

    return $result;
  }

  /**
   * Sets tasks for first group of tasks in initial sync (Attributes and Filter) to the list of sub tasks.
   *
   * @param array $subTasks
   */
  protected function setFieldsTasks(&$subTasks) {
    // Class name and percentage of progress this task takes from the overall progress.
    $subTasks[$this->getAttributesSyncTaskName()] = 15;
    $subTasks[$this->getFilterSyncTaskName()] = 15;
  }

  /**
   * Gets overall progress of tasks belonging to fields task group.
   *
   * @return float
   */
  protected function getFieldsTasksProgress() {
    return $this->taskProgressMap[$this->getAttributesSyncTaskName()] / 2 +
            $this->taskProgressMap[$this->getFilterSyncTaskName()] / 2;
  }

  /**
   * Sets tasks for the last group in initial sync (recipients) to the list of sub tasks.
   *
   * @param array $subTasks
   */
  protected function setRecipientSyncTasks(&$subTasks) {
    $subTasks[$this->getRecipientSyncTaskName()] = $this->getConfigService()->isProductSearchEnabled() ? 60 : 65;
  }

  /**
   * Gets overall progress of tasks belonging to fields task group.
   *
   * @return float
   */
  protected function getRecipientSyncTasksProgress() {
    return $this->taskProgressMap[$this->getRecipientSyncTaskName()];
  }

  /**
   * Gets the key for the attributes sync task.
   *
   * @return string
   */
  protected function getAttributesSyncTaskName() {
    return AttributesSyncTask::getClassName();
  }

  /**
   * Gets the name of the filter sync task.
   *
   * @return string
   */
  protected function getFilterSyncTaskName() {
    return FilterSyncTask::getClassName();
  }

  /**
   * Gets the key for the group sync task.
   *
   * @return string
   */
  protected function getGroupSyncTaskName() {
    return GroupSyncTask::getClassName();
  }

  /**
   * Gets the key for the product search sync task.
   *
   * @return string
   */
  protected function getProductSearchSyncTaskName() {
    return ProductSearchSyncTask::getClassName();
  }

  /**
   * Gets the key for the recipient sync task.
   *
   * @return string
   */
  protected function getRecipientSyncTaskName() {
    return RecipientSyncTask::getClassName();
  }

  /**
   *
   */
  protected function makeAttributesSyncTask() {
    return new AttributesSyncTask();
  }

  /**
   *
   */
  protected function makeFilterSyncTask() {
    return new FilterSyncTask();
  }

  /**
   *
   */
  protected function makeGroupSyncTask() {
    return new GroupSyncTask();
  }

  /**
   *
   */
  protected function makeProductSearchSyncTask() {
    return new ProductSearchSyncTask();
  }

  /**
   *
   */
  protected function makeRecipientSyncTask() {
    $recipientSyncService = ServiceRegister::getService(Recipients::CLASS_NAME);
    $allRecipientsIds = $recipientSyncService->getAllRecipientsIds();

    return new RecipientSyncTask($allRecipientsIds, NULL, TRUE);
  }

}
