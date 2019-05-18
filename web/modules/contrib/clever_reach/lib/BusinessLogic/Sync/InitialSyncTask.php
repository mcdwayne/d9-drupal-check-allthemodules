<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\CompositeTask;

/**
 * Class InitialSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class InitialSyncTask extends CompositeTask
{
    /**
     * InitialSyncTask constructor.
     *
     * @param array $subTasks List of sub tasks.
     * @param int $initialProgress Initial progress percentage.
     */
    public function __construct(array $subTasks = array(), $initialProgress = 0)
    {
        if (empty($subTasks)) {
            $this->setSubscriberListTasks($subTasks);
            $this->setFieldsTasks($subTasks);
            $this->setRecipientSyncTasks($subTasks);
        }

        parent::__construct($subTasks, $initialProgress);
    }

    /**
     * Returns progress by initial sync task groups:
     *
     * - First group: Group sync and Product search
     * - Second group: Attributes and Filter
     * - Third group: Recipients.
     *
     * @return array
     *   Initial sync task group as keys and progress as value.
     */
    public function getProgressByTask()
    {
        return array(
            'subscriberList' => $this->getSubscriberListTasksProgress(),
            'fields' => $this->getFieldsTasksProgress(),
            'recipients' => $this->getRecipientSyncTasksProgress(),
        );
    }

    /**
     * Gets count of synchronized recipients.
     *
     * @return int
     *   Number of synced recipients.
     */
    public function getSyncedRecipientsCount()
    {
        /** @var RecipientSyncTask $recipientTask */
        $recipientTask = $this->getSubTask($this->getRecipientSyncTaskName());

        return $recipientTask->getNumberOfRecipientsForSync() - count($recipientTask->getRecipientsIdsForSync());
    }

    /**
     * Runs task execution.
     *
     * @inheritdoc
     */
    public function execute()
    {
        parent::execute();

        $this->getConfigService()->setImportStatisticsDisplayed(false);
        $this->getConfigService()->setNumberOfSyncedRecipients($this->getSyncedRecipientsCount());
    }

    /**
     * Creates sub task for provided unique task key.
     *
     * Supported types:
     *  AttributesSyncTask
     *  FilterSyncTask
     *  GroupSyncTask
     *  ProductSearchSyncTask
     *  RecipientSyncTask
     *
     * @param string $taskKey Unique task key, class name is used as identifier.
     *
     * @return BaseSyncTask
     *   Instance of created task.
     */
    protected function createSubTask($taskKey)
    {
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
            case $this->getRegisterEventHandlerTaskName():
                return $this->makeRegisterEventHandlerTask();
        }

        throw new \RuntimeException('Unknown task type! ' . $taskKey);
    }

    /**
     * Sets tasks for first group in initial sync to the list of sub tasks.
     *
     * First group: Group sync and Product search
     *
     * @param array $subTasks List of sub tasks used in this task.
     */
    protected function setSubscriberListTasks(array &$subTasks)
    {
        $subTasks[$this->getGroupSyncTaskName()] = 5;
        $subTasks[$this->getRegisterEventHandlerTaskName()] = 5;

        if ($this->getConfigService()->isProductSearchEnabled()) {
            $subTasks[$this->getProductSearchSyncTaskName()] = 5;
        }
    }

    /**
     * Gets overall progress of tasks belonging to subscriber list task group.
     *
     * @return float
     *   Current progress of subscriber list.
     */
    protected function getSubscriberListTasksProgress()
    {
        $result = $this->taskProgressMap[$this->getGroupSyncTaskName()] / 2 +
            $this->taskProgressMap[$this->getRegisterEventHandlerTaskName()] / 2;

        if (isset($this->taskProgressMap[$this->getProductSearchSyncTaskName()])) {
            $result = $result * 2 / 3 + $this->taskProgressMap[$this->getProductSearchSyncTaskName()] / 3;
        }

        return $result;
    }

    /**
     * Sets tasks for second group in initial sync to the list of sub tasks.
     *
     * Second group: Attributes and Filter.
     *
     * @param array $subTasks List of sub tasks.
     */
    protected function setFieldsTasks(array &$subTasks)
    {
        // Class name and percentage of progress this
        // task takes from the overall progress
        $subTasks[$this->getAttributesSyncTaskName()] = 15;
        $subTasks[$this->getFilterSyncTaskName()] = 15;
    }

    /**
     * Gets overall progress of tasks belonging to fields task group.
     *
     * @return float
     *   Current progress of fields task group.
     */
    protected function getFieldsTasksProgress()
    {
        return $this->taskProgressMap[$this->getAttributesSyncTaskName()] / 2 +
            $this->taskProgressMap[$this->getFilterSyncTaskName()] / 2;
    }

    /**
     * Sets tasks for the last group in initial sync to the list of sub tasks.
     *
     * Third group: Recipients.
     *
     * @param array $subTasks List of sub tasks.
     */
    protected function setRecipientSyncTasks(array &$subTasks)
    {
        $subTasks[$this->getRecipientSyncTaskName()] = $this->getConfigService()->isProductSearchEnabled() ? 55 : 60;
    }

    /**
     * Gets overall progress of tasks belonging to fields task group.
     *
     * @return float
     *   Current progress of recipient task group.
     */
    protected function getRecipientSyncTasksProgress()
    {
        return $this->taskProgressMap[$this->getRecipientSyncTaskName()];
    }

    /**
     * Gets the key for the attributes sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getAttributesSyncTaskName()
    {
        return AttributesSyncTask::getClassName();
    }

    /**
     * Gets the name of the filter sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getFilterSyncTaskName()
    {
        return FilterSyncTask::getClassName();
    }

    /**
     * Gets the key for the group sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getGroupSyncTaskName()
    {
        return GroupSyncTask::getClassName();
    }

    /**
     * Gets the key for the product search sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getProductSearchSyncTaskName()
    {
        return ProductSearchSyncTask::getClassName();
    }

    /**
     * Gets the key for the recipient sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getRecipientSyncTaskName()
    {
        return RecipientSyncTask::getClassName();
    }

    /**
     * Gets the key for the register event handler sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getRegisterEventHandlerTaskName()
    {
        return RegisterEventHandlerTask::getClassName();
    }

    /**
     * Creates new task instance.
     *
     * @return AttributesSyncTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\AttributesSyncTask.
     */
    protected function makeAttributesSyncTask()
    {
        return new AttributesSyncTask();
    }

    /**
     * Creates new task instance.
     *
     * @return FilterSyncTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\FilterSyncTask.
     */
    protected function makeFilterSyncTask()
    {
        return new FilterSyncTask();
    }

    /**
     * Creates new task instance.
     *
     * @return GroupSyncTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\GroupSyncTask.
     */
    protected function makeGroupSyncTask()
    {
        return new GroupSyncTask();
    }

    /**
     * Creates new task instance.
     *
     * @return ProductSearchSyncTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\ProductSearchSyncTask.
     */
    protected function makeProductSearchSyncTask()
    {
        return new ProductSearchSyncTask();
    }

    /**
     * Creates new task instance.
     *
     * @return RegisterEventHandlerTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\RegisterEventHandlerTask.
     */
    protected function makeRegisterEventHandlerTask()
    {
        return new RegisterEventHandlerTask();
    }

    /**
     * Creates new task instance.
     *
     * @return RecipientSyncTask
     *   Instance of @see \CleverReach\BusinessLogic\Sync\RecipientSyncTask.
     */
    protected function makeRecipientSyncTask()
    {
        $recipientSyncService = ServiceRegister::getService(Recipients::CLASS_NAME);
        $allRecipientsIds = $recipientSyncService->getAllRecipientsIds();

        return new RecipientSyncTask($allRecipientsIds, null, true);
    }
}
