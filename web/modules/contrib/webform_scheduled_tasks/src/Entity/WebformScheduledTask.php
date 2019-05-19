<?php

namespace Drupal\webform_scheduled_tasks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\webform\Entity\Webform;

/**
 * Defines the webform schedule entity type.
 *
 * @ConfigEntityType(
 *   id = "webform_scheduled_task",
 *   label = @Translation("Scheduled task"),
 *   label_collection = @Translation("Scheduled tasks"),
 *   label_singular = @Translation("scheduled task"),
 *   label_plural = @Translation("scheduled tasks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count scheduled task",
 *     plural = "@count scheduled tasks",
 *   ),
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "\Drupal\webform_scheduled_tasks\WebformScheduledTaskListBuilder",
 *     "form" = {
 *       "add" = "\Drupal\webform_scheduled_tasks\Form\WebformScheduledTaskForm",
 *       "edit" = "\Drupal\webform_scheduled_tasks\Form\WebformScheduledTaskForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webform_scheduled_tasks\ScheduledTaskRouteProvider",
 *     },
 *   },
 *   config_prefix = "scheduled_task",
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/manage/{webform}/scheduled-tasks/add",
 *     "edit-form" = "/admin/structure/webform/manage/{webform}/scheduled-tasks/{webform_scheduled_task}/edit",
 *     "delete-form" = "/admin/structure/webform/manage/scheduled-tasks/{webform_scheduled_task}/delete",
 *     "collection" = "/admin/structure/webform/manage/{webform}/scheduled-tasks",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "task_type",
 *     "result_set_type",
 *     "task_settings",
 *     "result_set_settings",
 *     "interval",
 *     "webform",
 *   },
 * )
 */
class WebformScheduledTask extends ConfigEntityBase implements WebformScheduledTaskInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of the scheduled task.
   *
   * @var string
   */
  protected $id;

  /**
   * The task type.
   *
   * @var string
   */
  protected $task_type;

  /**
   * The result set type.
   *
   * @var string
   */
  protected $result_set_type;

  /**
   * A list of task settings.
   *
   * @var array
   */
  protected $task_settings = [];

  /**
   * A the result set settings.
   *
   * @var array
   */
  protected $result_set_settings = [];

  /**
   * The webform ID the schedule is attached to.
   *
   * @var string
   */
  protected $webform;

  /**
   * The interval values for the task.
   *
   * @var array
   */
  protected $interval = [];

  /**
   * The scheduling state service.
   *
   * @var \Drupal\webform_scheduled_tasks\SchedulingStateInterface
   */
  protected $scheduleState;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'task_settings' => $this->getPluginCollection('plugin.manager.webform_scheduled_tasks.task', $this->task_type, $this->task_settings),
      'result_set_settings' => $this->getPluginCollection('plugin.manager.webform_scheduled_tasks.result_set', $this->result_set_type, $this->result_set_settings),
    ];
  }

  /**
   * Get a plugin collection from a manager, type and settings.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection|null
   *   A collection, or NULL if none is instantiable yet.
   */
  protected function getPluginCollection($plugin_manager, $type, $settings) {
    // Plugin collections must be stored as a single root property on the entity
    // so that the serialization traits unset it correctly.
    $key = 'plugin_collection_' . $type;
    if (empty($this->$key) && !empty($type)) {
      $this->$key = new DefaultSingleLazyPluginCollection(\Drupal::service($plugin_manager), $type, $settings);
    }
    return !empty($this->$key) ? $this->$key : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskPlugin() {
    return $this->getPluginCollections()['task_settings']
      ->get($this->task_type)
      ->setScheduledTask($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getResultSetPlugin() {
    return $this->getPluginCollections()['result_set_settings']
      ->get($this->result_set_type)
      ->setScheduledTask($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    return parent::urlRouteParameters($rel) + [
      'webform' => $this->webform,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRunIntervalAmount() {
    return isset($this->interval['amount']) ? $this->interval['amount'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunIntervalMultiplier() {
    return isset($this->interval['multiplier']) ? $this->interval['multiplier'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function registerSuccessfulTask() {
    $this->getResultSetPlugin()->onSuccess($this->getWebform());
    $this->getTaskPlugin()->onSuccess($this->getWebform());
  }

  /**
   * {@inheritdoc}
   */
  public function registerFailedTask(\Exception $e = NULL) {
    $this->getResultSetPlugin()->onFailure($this->getWebform());
    $this->getTaskPlugin()->onFailure($this->getWebform());
  }

  /**
   * {@inheritdoc}
   */
  public function setNextTaskRunDate($timestamp) {
    $this->getScheduleState()->setNextScheduledTime($this, $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextTaskRunDate() {
    // If this task has not had a time scheduled and we have set both run
    // intervals, initialize the next run date of the task, to ensure this
    // method always return something usable in a scheduling context.
    if (!$this->getScheduleState()->hasTimeScheduled($this) && $this->getRunIntervalMultiplier() !== NULL && $this->getRunIntervalAmount() !== NULL) {
      $this->incrementTaskRunDateByInterval();
    }
    return $this->getScheduleState()->getNextScheduledTime($this);
  }

  /**
   * {@inheritdoc}
   */
  public function incrementTaskRunDateByInterval() {
    $this->setNextTaskRunDate($this->getTime()->getRequestTime() + ($this->getRunIntervalMultiplier() * $this->getRunIntervalAmount()));
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return Webform::load($this->webform);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency($this->getWebform()->getConfigDependencyKey(), $this->getWebform()->getConfigDependencyName());
    return $this;
  }

  /**
   * Get the schedule state service.
   *
   * @return \Drupal\webform_scheduled_tasks\SchedulingStateInterface
   *   The state service.
   */
  protected function getScheduleState() {
    if (!isset($this->scheduleState)) {
      $this->scheduleState = \Drupal::service('webform_scheduled_tasks.scheduling_state');
    }
    return $this->scheduleState;
  }

  /**
   * Get the time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  protected function getTime() {
    if (!isset($this->time)) {
      $this->time = \Drupal::service('datetime.time');
    }
    return $this->time;
  }

  /**
   * {@inheritdoc}
   */
  public function halt($reason = '') {
    $this->getScheduleState()->haltTask($this, $reason);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resume() {
    $this->getScheduleState()->resumeTask($this);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isHalted() {
    return $this->getScheduleState()->isHalted($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getHaltedReason() {
    return $this->getScheduleState()->getHaltedMessage($this);
  }

}
