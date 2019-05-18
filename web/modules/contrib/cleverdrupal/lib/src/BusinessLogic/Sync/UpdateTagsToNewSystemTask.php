<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Entity\TagInOldFormat;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\CompositeTask;

/**
 *
 */
class UpdateTagsToNewSystemTask extends CompositeTask {
  const INITIAL_SYNC_PROGRESS = 1;

  const NUMBER_OF_SUBTASKS = 3;

  /**
   * @var \CleverReach\BusinessLogic\Interfaces\Recipients
   */
  private $recipientService;
  /**
   * @var array
   */
  private $subTasks = [];
  /**
   * @var array
   */
  private $prefixedShopTags;

  /**
   * UpdateTagsToNewSystemTask constructor.
   *
   * @param array $prefixedShopTags
   */
  public function __construct(array $prefixedShopTags = []) {
    $this->prefixedShopTags = $prefixedShopTags;
    $this->setDeletePrefixedFilterTask();
    $this->setFilterSyncTask();
    $this->setRecipientSyncTasks();

    parent::__construct($this->subTasks, self::INITIAL_SYNC_PROGRESS);
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize(
        [
          'taskProgress' => $this->taskProgressMap,
          'subTasksProgressShare' => $this->subTasksProgressShare,
          'tasks' => $this->tasks,
          'prefixedShopTags' => $this->prefixedShopTags,
        ]
    );
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $unserializedStateData = unserialize($serialized);

    $this->taskProgressMap = $unserializedStateData['taskProgress'];
    $this->subTasksProgressShare = $unserializedStateData['subTasksProgressShare'];
    $this->tasks = $unserializedStateData['tasks'];
    $this->prefixedShopTags = $unserializedStateData['prefixedShopTags'];

    $this->attachSubTasksEvents();
  }

  /**
   * Returns progress by 2 tasks (keys in returned array): deletePrefixedFilters and recipients.
   *
   * @return array
   */
  public function getProgressByTask() {
    return [
      'deletePrefixedFilters' => $this->getDeletePrefixedFiltersTaskProgress(),
      'filters' => $this->getFilterSyncTaskProgress(),
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
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
   */
  protected function createSubTask($taskKey) {
    switch ($taskKey) {
      case $this->getDeletePrefixedFilterTaskName():
        return $this->makeDeletePrefixedFilterSyncTask();

      case $this->getFilterSyncTaskName():
        return $this->makeFilterSyncTask();

      case $this->getRecipientSyncTaskName():
        return $this->makeRecipientSyncTask();
    }

    throw new \RuntimeException('Unknown task type! ' . $taskKey);
  }

  /**
   * Sets DeletePrefixedFilterSyncTask to the list of sub tasks.
   */
  protected function setDeletePrefixedFilterTask() {
    $this->subTasks[$this->getDeletePrefixedFilterTaskName()] = $this->getPercentageOfTheOverallProgress();
  }

  /**
   * Gets overall progress of tasks belonging to delete prefixed filter task.
   *
   * @return float
   */
  protected function getDeletePrefixedFiltersTaskProgress() {
    return $this->taskProgressMap[$this->getDeletePrefixedFilterTaskName()];
  }

  /**
   *
   */
  protected function setFilterSyncTask() {
    $this->subTasks[$this->getFilterSyncTaskName()] = $this->getPercentageOfTheOverallProgress();
  }

  /**
   * Gets overall progress of tasks belonging to filter sync task.
   *
   * @return float
   */
  protected function getFilterSyncTaskProgress() {
    return $this->taskProgressMap[$this->getFilterSyncTaskName()];
  }

  /**
   * Sets RecipientSyncTask to the list of sub tasks.
   */
  protected function setRecipientSyncTasks() {
    $this->subTasks[$this->getRecipientSyncTaskName()] = $this->getPercentageOfTheOverallProgress();
  }

  /**
   * Gets overall progress of tasks belonging to recipients task group.
   *
   * @return float
   */
  protected function getRecipientSyncTasksProgress() {
    return $this->taskProgressMap[$this->getRecipientSyncTaskName()];
  }

  /**
   * Gets the key for the group sync task.
   *
   * @return string
   */
  protected function getDeletePrefixedFilterTaskName() {
    return DeletePrefixedFilterSyncTask::getClassName();
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
  protected function makeDeletePrefixedFilterSyncTask() {
    return new DeletePrefixedFilterSyncTask($this->prefixedShopTags);
  }

  /**
   *
   */
  protected function makeFilterSyncTask() {
    return new FilterSyncTask();
  }

  /**
   * @return \CleverReach\BusinessLogic\Sync\RecipientSyncTask
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
   */
  protected function makeRecipientSyncTask() {
    $allRecipientsIds = $this->getRecipientService()->getAllRecipientsIds();
    $tagsForDelete = $this->formatTagsForDelete();
    return new RecipientSyncTask($allRecipientsIds, $tagsForDelete, FALSE);
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  private function formatTagsForDelete() {
    $tagCollection = new TagCollection();
    if ($this->prefixedShopTags === NULL) {
      return $tagCollection;
    }

    foreach ($this->prefixedShopTags as $prefixedShopTag) {
      $tagInOldFormat = $this->getTagForDelete($prefixedShopTag);
      $tagCollection->addTag($tagInOldFormat);
    }

    return $tagCollection;
  }

  /**
   * @param string $prefixedShopTag
   *
   * @return \CleverReach\BusinessLogic\Entity\Tag
   */
  private function getTagForDelete($prefixedShopTag) {
    $tag = new TagInOldFormat($prefixedShopTag);
    $tag->markDeleted();

    return $tag;
  }

  /**
   * @return \CleverReach\BusinessLogic\Interfaces\Recipients
   */
  private function getRecipientService() {
    if ($this->recipientService === NULL) {
      $this->recipientService = ServiceRegister::getService(Recipients::CLASS_NAME);
    }

    return $this->recipientService;
  }

  /**
   * @return float|int
   */
  private function getPercentageOfTheOverallProgress() {
    return (100 - self::INITIAL_SYNC_PROGRESS) / self::NUMBER_OF_SUBTASKS;
  }

}
