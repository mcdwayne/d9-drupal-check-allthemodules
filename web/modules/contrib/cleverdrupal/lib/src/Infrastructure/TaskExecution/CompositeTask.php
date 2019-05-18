<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;

/**
 * This type of task should be used when there is a need for synchronous execution of several tasks.
 *
 * @package CleverReach\Infrastructure\TaskExecution
 */
abstract class CompositeTask extends Task {
  /**
   * @var array
   */
  protected $taskProgressMap = [];
  /**
   * @var array
   */
  protected $subTasksProgressShare = [];
  /**
   * @var Task[]
   */
  protected $tasks = [];
  /**
   * @var int
   */
  private $initialProgress;

  /**
   *
   */
  public function __construct(array $subTasks, $initialProgress = 0) {
    $this->initialProgress = $initialProgress;

    $this->taskProgressMap = [
      'overallTaskProgress' => 0,
    ];

    $this->subTasksProgressShare = [];

    foreach ($subTasks as $subTaskKey => $subTaskProgressShare) {
      $this->taskProgressMap[$subTaskKey] = 0;
      $this->subTasksProgressShare[$subTaskKey] = $subTaskProgressShare;
    }
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize(
        [
          'initialProgress' => $this->initialProgress,
          'taskProgress' => $this->taskProgressMap,
          'subTasksProgressShare' => $this->subTasksProgressShare,
          'tasks' => $this->tasks,
        ]
    );
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $unserializedStateData = unserialize($serialized);

    $this->initialProgress = $unserializedStateData['initialProgress'];
    $this->taskProgressMap = $unserializedStateData['taskProgress'];
    $this->subTasksProgressShare = $unserializedStateData['subTasksProgressShare'];
    $this->tasks = $unserializedStateData['tasks'];

    $this->attachSubTasksEvents();
  }

  /**
   * @inheritdoc
   */
  public function execute() {
    while ($activeTask = $this->getActiveTask()) {
      $activeTask->execute();
    }
  }

  /**
   * @inheritdoc
   */
  public function canBeReconfigured() {
    $activeTask = $this->getActiveTask();

    return $activeTask !== NULL ? $activeTask->canBeReconfigured() : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function reconfigure() {
    $activeTask = $this->getActiveTask();

    if ($activeTask !== NULL) {
      $activeTask->reconfigure();
    }
  }

  /**
   * Gets progress by each task.
   *
   * @return array
   */
  public function getProgressByTask() {
    return $this->taskProgressMap;
  }

  /**
   * @param $taskKey
   *
   * @return Task
   */
  abstract protected function createSubTask($taskKey);

  /**
   * @return Task|null
   */
  protected function getActiveTask() {
    $task = NULL;
    foreach ($this->taskProgressMap as $taskKey => $taskProgress) {
      if ($taskKey === 'overallTaskProgress') {
        continue;
      }

      if ($taskProgress < 100) {
        $task = $this->getSubTask($taskKey);

        break;
      }
    }

    return $task;
  }

  /**
   * Gets sub task by the task key. If sub task does not exist, creates it.
   *
   * @param string $taskKey
   *
   * @return \CleverReach\Infrastructure\TaskExecution\Task
   */
  protected function getSubTask($taskKey) {
    if (empty($this->tasks[$taskKey])) {
      $this->tasks[$taskKey] = $this->createSubTask($taskKey);
      $this->attachSubTaskEvents($this->tasks[$taskKey]);
    }

    return $this->tasks[$taskKey];
  }

  /**
   * Attaches "report progress" and "report alive" events to all sub tasks.
   */
  protected function attachSubTasksEvents() {
    foreach ($this->tasks as $task) {
      $this->attachSubTaskEvents($task);
    }
  }

  /**
   * Attaches "report progress" and "report alive" events to a sub task.
   *
   * @param \CleverReach\Infrastructure\TaskExecution\Task $task
   */
  protected function attachSubTaskEvents(Task $task) {
    $this->attachReportAliveEvent($task);
    $this->attachReportProgressEvent($task);
  }

  /**
   * @param float $subTaskProgress
   * @param string $subTaskProgressMapKey
   */
  protected function calculateProgress($subTaskProgress, $subTaskProgressMapKey) {
    $this->taskProgressMap[$subTaskProgressMapKey] = $subTaskProgress;
    $overallProgress = 0;

    foreach ($this->subTasksProgressShare as $subTaskKey => $subTaskPercentageShare) {
      $overallProgress += $this->taskProgressMap[$subTaskKey] * $subTaskPercentageShare / 100;
    }

    $this->taskProgressMap['overallTaskProgress'] = $this->initialProgress + $overallProgress;

    if ($this->isProcessCompleted()) {
      $this->taskProgressMap['overallTaskProgress'] = 100;
    }
  }

  /**
   * Checks if all sub tasks are finished.
   *
   * @return bool
   */
  protected function isProcessCompleted() {
    $allTasksSuccessful = TRUE;

    foreach (array_keys($this->subTasksProgressShare) as $subTaskKey) {
      if ($this->taskProgressMap[$subTaskKey] < 100) {
        $allTasksSuccessful = FALSE;
        break;
      }
    }

    return $allTasksSuccessful;
  }

  /**
   *
   */
  private function attachReportAliveEvent(Task $task) {
    $self = $this;

    $task->when(
        AliveAnnouncedTaskEvent::CLASS_NAME,
        function () use ($self) {
            $self->reportAlive();
        }
    );
  }

  /**
   *
   */
  private function attachReportProgressEvent(Task $task) {
    $self = $this;

    $task->when(
        ProgressedTaskEvent::CLASS_NAME,
        function (ProgressedTaskEvent $event) use ($self, $task) {
            $self->calculateProgress($event->getProgressFormatted(), $task->getType());
            $self->reportProgress($self->taskProgressMap['overallTaskProgress']);
        }
    );
  }

}
