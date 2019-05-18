<?php

namespace Drupal\Tests\cleverreach\Unit;

use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\GenericTests\GenericTestException;
use CleverReach\Tests\GenericTests\TestComponents\FakeTask;

/**
 *
 */
class BaseQueueItem {
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage
   */
  private $storage;

  /**
   * @var \CleverReach\Infrastructure\TaskExecution\QueueItem[]
   */
  private $testItems = [];

  /**
   *
   */
  public function __construct(TaskQueueStorage $storage) {
    $this->storage = $storage;

    $timeProvider = new TimeProvider();

    new ServiceRegister(
        [
          TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                return $timeProvider;
          },
        ]
    );
  }

  /**
   *
   */
  public function test() {
    try {
      $this->setupStorageState();
    }
    catch (\Exception $ex) {
      throw new GenericTestException('Failed to save new queue item in storage.', 0, $ex);
    }

    $this->testFind();
    $this->testFindOldestQueuedItems();
    $this->testFindAll();
    $this->testFindAllWithStartAndLimit();
    $this->testSaveRestricted();
  }

  /**
   *
   */
  public function testFindAllWithStartAndLimit() {
    $filterBy = ['status' => QueueItem::QUEUED, 'queueName' => 'Queue3'];
    $sortBy = ['queueTimestamp' => TaskQueueStorage::SORT_ASC];
    $start = 1;
    $limit = 2;

    $result = $this->storage->findAll($filterBy, $sortBy, $start, $limit);

    if (count($result) !== $limit) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Expected %d returned items, but got %d.',
            $this->getMethodCallAsString('findAll', $filterBy, $sortBy, $start, $limit),
            $limit,
            count($result)
        )
      );
    }

    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem[] $resultById */
    $resultById = [];
    foreach ($result as $item) {
      $resultById[$item->getId()] = $item;
    }

    if (
        !array_key_exists($this->testItems['Task_Q3T2']->getId(), $resultById) ||
        !array_key_exists($this->testItems['Task_Q3T3']->getId(), $resultById)
    ) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Expected item ids in result: %s but got: %s',
            $this->getMethodCallAsString('findAll', $filterBy, $sortBy, $start, $limit),
            join(', ', [$this->testItems['Task_Q3T2']->getId(), $this->testItems['Task_Q3T3']->getId()]),
            join(', ', array_keys($resultById))
        )
      );
    }
  }

  /**
   *
   */
  private function setupStorageState() {
    $queueItems = $this->getTestQueueItems();

    foreach ($queueItems as $itemKey => $queueItem) {
      $savedItemId = $this->storage->save($queueItem);
      $queueItem->setId($savedItemId);
      if ($savedItemId === NULL) {
        throw new \Exception(
        'Failed to save new queue item with name:' . $queueItem->getQueueName() . 'in storage.'
        );
      }
      $this->testItems[$itemKey] = $queueItem;
    }
  }

  /**
   * @return \CleverReach\Infrastructure\TaskExecution\QueueItem[]
   */
  private function getTestQueueItems() {
    $queueItems = [];
    $baseDateTime = new \DateTime('now -10 days');

    // Queue 1.
    $queueItem = new QueueItem(new FakeTask('Task_Q1T1'));
    $queueItem->setQueueName('Queue1');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q1T1'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q1T2'));
    $queueItem->setQueueName('Queue1');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q1T2'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q1T3'));
    $queueItem->setQueueName('Queue1');
    $queueItem->setStatus(QueueItem::COMPLETED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItem->setFinishTimestamp($baseDateTime->getTimestamp());
    $queueItem->setProgressBasePoints(10000);
    $queueItems['Task_Q1T3'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q1T4'));
    $queueItem->setQueueName('Queue1');
    $queueItem->setStatus(QueueItem::FAILED);
    $queueItem->setRetries(10);
    $queueItem->setFailureDescription('Failed test task');
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItem->setFailTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q1T4'] = $queueItem;

    // Queue 2.
    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q2T1'));
    $queueItem->setQueueName('Queue2');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q2T1'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q2T2'));
    $queueItem->setQueueName('Queue2');
    $queueItem->setStatus(QueueItem::IN_PROGRESS);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItem->setStartTimestamp($baseDateTime->getTimestamp());
    $queueItem->setLastUpdateTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q2T2'] = $queueItem;

    // Queue 3.
    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q3T1'));
    $queueItem->setQueueName('Queue3');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q3T1'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q3T2'));
    $queueItem->setQueueName('Queue3');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q3T2'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q3T3'));
    $queueItem->setQueueName('Queue3');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q3T3'] = $queueItem;

    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q3T4'));
    $queueItem->setQueueName('Queue3');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItems['Task_Q3T4'] = $queueItem;

    // Queue 4.
    $baseDateTime->modify('+1 day');
    $queueItem = new QueueItem(new FakeTask('Task_Q4T1'));
    $queueItem->setQueueName('Queue4');
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueTimestamp($baseDateTime->getTimestamp());
    $queueItem->setLastExecutionProgressBasePoints(3567);
    $queueItems['Task_Q4T1'] = $queueItem;

    return $queueItems;
  }

  /**
   *
   */
  private function testFind() {
    $testItem = $this->testItems['Task_Q4T1'];

    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $itemFromStorage */
    $itemFromStorage = $this->storage->find($testItem->getId());

    if (empty($itemFromStorage)) {
      throw new GenericTestException('Failed to find saved queue item in storage by item id.');
    }

    if ($this->getItemArray($testItem) != $this->getItemArray($itemFromStorage)) {
      throw new GenericTestException(
        var_export(
            [
              'Message' => 'Saved item in storage is not equeal to initial queue item.',
              'InitialItem' => $this->getItemArray($testItem),
              'ItemFromStorage' => $this->getItemArray($itemFromStorage),
            ],
            TRUE
        )
      );
    }
  }

  /**
   *
   */
  private function getItemArray(QueueItem $item) {
    return [
      'id' => $item->getId(),
      'status' => $item->getStatus(),
      'type' => $item->getTaskType(),
      'queueName' => $item->getQueueName(),
      'context' => $item->getContext(),
      'lastExecutionProgress' => $item->getLastExecutionProgressBasePoints(),
      'progress' => $item->getProgressBasePoints(),
      'retries' => $item->getRetries(),
      'failureDescription' => $item->getFailureDescription(),
      'createTimestamp' => $item->getCreateTimestamp(),
      'queueTimestamp' => $item->getQueueTimestamp(),
      'lastUpdateTimestamp' => $item->getLastUpdateTimestamp(),
      'startTimestamp' => $item->getStartTimestamp(),
      'finishTimestamp' => $item->getFinishTimestamp(),
      'failTimestamp' => $item->getFinishTimestamp(),
      'earliestStartTimestamp' => $item->getEarliestStartTimestamp(),
    ];
  }

  /**
   *
   */
  private function testFindOldestQueuedItems() {
    $result = $this->storage->findOldestQueuedItems(2);

    if (count($result) !== 0) {
      throw new GenericTestException(
        'Method findOldestQueuedItems failed. Method should respect result limit and return (one) oldest ' .
        'queued item per queue and for all queues that does not have items in progress.'
      );
    }

    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem[] $itemsByQueue */
    $itemsByQueue = [];
    foreach ($result as $storageItem) {
      $itemsByQueue[$storageItem->getQueueName()] = $storageItem;
    }

    if (array_key_exists('Queue2', $itemsByQueue)) {
      throw new GenericTestException(
        sprintf(
            'Method findOldestQueuedItems failed. Queue named Queue2 contains running item ' .
            '(item with status %s). Queues with running items must be skipped from result.',
            QueueItem::IN_PROGRESS
        )
      );
    }

    if (array_key_exists('Queue4', $itemsByQueue)) {
      throw new GenericTestException(
        sprintf(
            'Method findOldestQueuedItems failed. Returned result must be sorted by queue time. Item with ' .
            'id %s from Queue4 returned but Queue1 and Queue3 have tasks %s and %s with earliest queued items.',
            $this->testItems['Task_Q4T1']->getId(),
            $this->testItems['Task_Q1T1']->getId(),
            $this->testItems['Task_Q3T1']->getId()
        )
      );
    }
  }

  /**
   *
   */
  private function testFindAll() {
    $filter = ['status' => QueueItem::IN_PROGRESS];

    $result = $this->storage->findAll($filter);

    if (count($result) !== 1) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Filter criteria %s is not respected. Method returned %d items where %d is expected.',
            $this->getMethodCallAsString('findAll', $filter),
            json_encode($filter),
            count($result),
            1
        )
      );
    }

    $storageItem = current($result);
    if ($storageItem->getId() != $this->testItems['Task_Q2T2']->getId()) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Filter criteria %s is not respected. Method returned task with id %s but task with id %s is expected.',
            $this->getMethodCallAsString('findAll', $filter),
            json_encode($filter),
            $storageItem->getId(),
            $this->testItems['Task_Q2T2']->getId()
        )
      );
    }
  }

  /**
   *
   */
  private function testSaveRestricted() {
    $testItem = $this->testItems['Task_Q3T4'];

    $additionalWhere = ['status' => QueueItem::QUEUED];
    $itemId = $this->storage->save($testItem, $additionalWhere);

    if ($itemId !== $testItem->getId()) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Queue item update should return saved item id. Expected return value %s but got %s.',
            $this->getMethodCallAsString('save', 'QueueItem with id ' . $testItem->getId(), $additionalWhere),
            $testItem->getId(),
            $itemId
        )
      );
    }

    $exception = NULL;
    try {
      $additionalNotValidWhere = ['status' => QueueItem::IN_PROGRESS];
      $this->storage->save($testItem, $additionalNotValidWhere);
    }
    catch (QueueItemSaveException $ex) {
      $exception = $ex;
    }

    if (empty($exception)) {
      throw new GenericTestException(
        sprintf(
            'Method %s failed. Queue item update should throw QueueItemSaveException when update criteria is not met.',
            $this->getMethodCallAsString(
                'save',
                'QueueItem with id ' . $testItem->getId(),
                $additionalNotValidWhere
            )
        )
      );
    }
  }

  /**
   *
   */
  private function getMethodCallAsString() {
    $arguments = func_get_args();
    $argumentList = '';
    for ($i = 1, $iMax = count($arguments); $i < $iMax; $i++) {
      $argumentList .= json_encode($arguments[$i]) . ', ';
    }

    $argumentList = trim($argumentList, ", ");
    return "{$arguments[0]}({$argumentList})";
  }

}
