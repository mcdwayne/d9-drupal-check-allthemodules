<?php

namespace Drupal\log_monitor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\log_monitor\Condition\ConditionPluginInterface;
use Drupal\log_monitor\Scheduler\SchedulerPluginInterface;
use Drupal\log_monitor\Reaction\ReactionPluginInterface;

/**
 * Defines the Log monitor rule entity.
 *
 * @ConfigEntityType(
 *   id = "log_monitor_rule",
 *   label = @Translation("Log monitor rule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\log_monitor\LogMonitorRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\log_monitor\Form\LogMonitorRuleForm",
 *       "edit" = "Drupal\log_monitor\Form\LogMonitorRuleForm",
 *       "delete" = "Drupal\log_monitor\Form\LogMonitorRuleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\log_monitor\LogMonitorRuleHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "log_monitor_rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "condition_settings",
 *     "reaction_settings",
 *     "scheduler_settings",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/logging/monitor/log_monitor/rule/{log_monitor_rule}",
 *     "add-form" = "/admin/config/development/logging/monitor/log_monitor/rule/add",
 *     "edit-form" = "/admin/config/development/logging/monitor/log_monitor/rule/{log_monitor_rule}/edit",
 *     "delete-form" = "/admin/config/development/logging/monitor/log_monitor/rule/{log_monitor_rule}/delete",
 *     "collection" = "/admin/config/development/logging/monitor/log_monitor/rules"
 *   }
 * )
 */
class LogMonitorRule extends ConfigEntityBase implements LogMonitorRuleInterface {

  /**
   * The Log monitor rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Log monitor rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * Array of ConditionPlugins
   *
   * @var array
   */
  protected $conditions;

  /**
   * Array of ReactionPlugins
   *
   * @var array
   */
  protected $reactions;

  /**
   * @var \Drupal\log_monitor\Scheduler\SchedulerPluginInterface
   */
  protected $scheduler;

  protected $expire;

  /**
   * @param \Drupal\log_monitor\ConditionPluginInterface $condition
   *   Describe..
   */
  public function addCondition(ConditionPluginInterface $condition) {
    if($condition->id() === NULL) {
      $condition->setId($this->generateUniquePluginId($condition, array_keys($this->getConditions())));
    }
    $this->conditions[$condition->id()] = $condition;
  }

  /**
   * @return array
   */
  public function getConditions() {
    if(!isset($this->conditions)) {
      $this->conditions = [];
      foreach($this->getConditionsFromSettings() as $plugin) {
        $this->addCondition($plugin);
      }
    }
    return $this->conditions;
  }

  /**
   * @param $id
   * @return mixed
   */
  public function getCondition($id) {
    foreach($this->getConditions() as $condition) {
      if($condition->id() == $id) {
        return $condition;
      }
    }
  }

  /**
   * @param $id
   */
  public function removeCondition($id) {
    unset($this->conditions[$id]);
  }

  /**
   * @param \Drupal\log_monitor\Reaction\ReactionPluginInterface $reaction
   */
  public function addReaction(ReactionPluginInterface $reaction) {
    if($reaction->id() === NULL) {
      $reaction->setId($this->generateUniquePluginId($reaction, array_keys($this->getReactions())));
    }
    $this->reactions[$reaction->id()] = $reaction;

  }

  /**
   * @return array
   */
  public function getReactions() {
    if(!isset($this->reactions)) {
      $this->reactions = [];
      foreach($this->getReactionsFromSettings() as $plugin) {
        $this->addReaction($plugin);
      }
    }
    return $this->reactions;
  }

  /**
   * @param $id
   * @return mixed
   */
  public function getReaction($id) {
    foreach($this->getReactions() as $reaction) {
      if($reaction->id() == $id) {
        return $reaction;
      }
    }
  }

  /**
   * @param $id
   */
  public function removeReaction($id) {
    unset($this->reactions[$id]);
  }

  /**
   * @return \Drupal\log_monitor\Scheduler\SchedulerPluginInterface
   */
  public function getScheduler() {
    if(!isset($this->scheduler)) {
      $this->scheduler = $this->getSchedulerFromSettings();
    }
    return $this->scheduler;
  }

  /**
   * @param \Drupal\log_monitor\Scheduler\SchedulerPluginInterface $scheduler
   */
  public function setScheduler(SchedulerPluginInterface $scheduler) {
    $this->scheduler = $scheduler;
  }


  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->writeChangesToSettings();
    parent::preSave($storage);
  }

  protected function writeChangesToSettings() {
    $this->condition_settings = [];
    foreach($this->getConditions() as $id => $condition) {
      $this->condition_settings[$condition->id()] = $condition->getConfiguration();
    }
    $this->reaction_settings = [];
    foreach($this->getReactions() as $id => $reaction) {
      $this->reaction_settings[$reaction->id()] = $reaction->getConfiguration();
    }
    $this->scheduler_settings = [];
    if($scheduler = $this->getScheduler()) {
      $this->scheduler_settings = $scheduler->getConfiguration();
    }
  }

  protected function getConditionsFromSettings() {
    $plugins = [];
    if (!empty($this->condition_settings)) {
      foreach ($this->condition_settings as $id => $value) {
        $plugins[] = \Drupal::getContainer()
          ->get('plugin.manager.log_monitor.condition')
          ->createInstance($value['plugin_id'], $value);
      }
    }
    return $plugins;
  }
  protected function getReactionsFromSettings() {
    $plugins = [];
    if (!empty($this->reaction_settings)) {
      foreach ($this->reaction_settings as $id => $value) {
        $plugins[] = \Drupal::getContainer()
          ->get('plugin.manager.log_monitor.reaction')
          ->createInstance($value['plugin_id'], $value);
      }
    }
    return $plugins;
  }
  protected function getSchedulerFromSettings() {
    if (!empty($this->scheduler_settings)) {
      return  \Drupal::getContainer()
        ->get('plugin.manager.log_monitor.scheduler')
        ->createInstance($this->scheduler_settings['plugin_id'], $this->scheduler_settings);
    }
  }

  public function generateUniquePluginId($plugin, $existing_ids) {
    $count = 1;
    $machine_default = $plugin->getPluginId();
    while (in_array($machine_default, $existing_ids)) {
      $machine_default = $plugin->getPluginId() . '_' . ++$count;
    }
    return $machine_default;
  }

  public function queryConditionGroup($query) {
    $group = $query->andConditionGroup();
    foreach($this->getConditions() as $condition) {
      $condition->queryCondition($group);
    }
    return $group;
  }

  public function addDependentLog($log_id) {
    \Drupal::service('log_monitor.dependency_manager')->addDependency($log_id, $this);
  }

  public function removeDependentLog($log_id) {
    \Drupal::service('log_monitor.dependency_manager')->removeDependency($log_id, $this);
  }

  public function removeDependentLogs() {
    \Drupal::service('log_monitor.dependency_manager')->removeEntityDependencies($this);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      $this->removeDependentLogs();
    }
    parent::delete();
  }
}
