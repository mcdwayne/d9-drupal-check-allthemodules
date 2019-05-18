<?php

namespace Drupal\advanced_update;

use Drupal\advanced_update\Entity\AdvancedUpdateEntity;
use \Drupal\core\state\state;

/**
 * Class AdvancedUpdateManager: Manage updates reports and executions.
 *
 * @package Drupal\advanced_update
 */
class AdvancedUpdateManager {
  /**
   * Name of state in key_value table.
   */
  const UPDATE_STATE = 'advanced_update.update_state';
  /**
   * Used to apply an update.
   */
  const UP = 'up';
  /**
   * Used to apply a downgrade.
   */
  const DOWN = 'down';
  /**
   * Drupal service state.
   *
   * @var State:
   */
  protected $stateManager = NULL;
  /**
   * Array of update entities.
   *
   * @var array
   */
  protected $advancedUpdates = array();
  /**
   * Type of update self::UP or self::DOWN.
   *
   * @var null:
   */
  protected $updateType = NULL;
  /**
   * State of updates in database.
   *
   * @var array
   */
  protected $updateState = array();
  /**
   * Number of updates to perform.
   *
   * @var null
   */
  protected $updateDeepNumber = NULL;
  /**
   * Module name to update.
   *
   * @var null
   */
  protected $selectedModule = NULL;
  /**
   * Status of updated classes.
   *
   * @var array
   */
  protected $updateStatus = array();

  /**
   * AdvancedUpdateManager constructor.
   *
   * @param \Drupal\core\state\state $state_manager
   *    Manage states from table key_value.
   */
  public function __construct(state $state_manager) {
    $this->stateManager = $state_manager;
  }

  /**
   * Set the type of desired update self::UP or self::DOWN.
   *
   * @param string $update_type
   *    the type of update self::UP or self::DOWN.
   */
  protected function setUpdateType($update_type) {
    $this->updateType = self::UP;
    if (!empty($update_type) && $update_type === self::DOWN) {
      $this->updateType = self::DOWN;
    }
  }

  /**
   * Set the number of updates to perform.
   *
   * @param string $update_deep_number
   *    Number of updates to perform.
   */
  protected function setDeepNumber($update_deep_number) {
    if (is_numeric($update_deep_number)) {
      $this->updateDeepNumber = (int) $update_deep_number;
    }
  }

  /**
   * Set a specific module to update or downgrade.
   *
   * @param string $module
   *    Module name.
   */
  protected function setSelectedModule($module) {
    $this->selectedModule = $module;
  }

  /**
   * Get the type of advanced updates.
   *
   * @return string
   *    self:UP or self:DOWN.
   */
  public function getUpdateType() {
    return $this->updateType;
  }

  /**
   * Get the state of advanced updates from the key_value table.
   *
   * @return array
   *    Return an array of states sorted by module.
   */
  public function getUpdateState() {
    $update_state = $this->stateManager->get(self::UPDATE_STATE);
    if (!empty($update_state)) {
      $this->updateState = $update_state;
    }
    return $this->updateState;
  }

  /**
   * Set the state of advanced updates to key_value table.
   */
  protected function setUpdateState() {
    $this->stateManager->set(self::UPDATE_STATE, $this->updateState);
  }

  /**
   * Get all availables updates from database.
   *
   * @return array
   *    Update entities.
   */
  protected function getAdvancedUpdates() {
    // Get entities ids.
    $query = \Drupal::entityQuery('advanced_update_entity');
    if (!empty($this->selectedModule)) {
      $query->condition('module_name', $this->selectedModule);
    }
    $direction = 'ASC';
    // If it's a downgrade.
    if ($this->updateType === self::DOWN) {
      $direction = 'DESC';
    }
    $query->sort('date', $direction);
    $update_ids = $query->execute();

    // Get entities from ids.
    $storage = \Drupal::entityTypeManager()
      ->getStorage('advanced_update_entity');
    $updates = $storage->loadMultiple($update_ids);

    // Sorting by modules.
    $callback = function ($returned, $entity) {
      $returned[$entity->moduleName()][] = $entity;
      return $returned;
    };
    $this->advancedUpdates = array_reduce($updates, $callback, array());
    return $this->advancedUpdates;
  }

  /**
   * Apply selected Advanced Updates.
   */
  protected function applyUpdates() {
    foreach ($this->advancedUpdates as $update_entity) {
      $module = $update_entity->moduleName();
      $class = '\\Drupal\\' . $module . '\\AdvancedUpdate\\' . $update_entity->className();
      $advanced_update = new $class();
      $status_object = new AdvancedUpdateStatus($update_entity);

      try {
        if ($advanced_update->{$this->updateType}()) {
          $status_object->setStatus(AdvancedUpdateStatus::STATUS_DONE);
        }
        else {
          $status_object->setStatus(AdvancedUpdateStatus::STATUS_FAILED);
        }
      }
      catch (UpdateNotImplementedException $e) {
        $status_object->setMessage($e->getMessage());
        $status_object->setStatus(AdvancedUpdateStatus::STATUS_DONE);
      }
      catch (\Exception $e) {
        $status_object->setMessage($e->getMessage());
        $status_object->setStatus(AdvancedUpdateStatus::STATUS_FAILED);
      }

      $this->updateStatus[] = $status_object;
      if ($status_object->getStatus() === AdvancedUpdateStatus::STATUS_DONE) {
        $date = $update_entity->date();
        $this->updateState[$module] = $date;
        if ($this->updateType === self::DOWN) {
          $this->updateState[$module] = $date - 1;
        }
      }
      else {
        break;
      }
    }
    $this->setUpdateState();
  }

  /**
   * Filtering Advanced Updates.
   */
  protected function updatesSlicing() {
    $selected_updates = array();
    if (!empty($this->advancedUpdates)) {

      // Get all updates not performed.
      foreach ($this->advancedUpdates as $module_name => $updates) {
        foreach ($updates as $update_entity) {
          if ($this->isAvailableUpdate($this->updateType, $this->updateState, $update_entity)) {
            $selected_updates[] = $update_entity;
          }
        }
      }
    }

    // Only get the number of updates wanted by $this->updateDeepNumber
    // if a module was selected.
    if ($this->selectedModule && !empty($this->updateDeepNumber)) {
      $this->advancedUpdates = array_slice($selected_updates, 0, $this->updateDeepNumber);
    }
    else {
      $this->advancedUpdates = $selected_updates;
    }
  }

  /**
   * Check if an update was performed.
   *
   * @param string $update_type
   *    Type of update, self::UP or self::DOWN.
   * @param array $update_states
   *    Array of states from db.
   * @param AdvancedUpdateEntity $advanced_update_entity
   *    Object of type AdvancedUpdateEntity.
   *
   * @return bool
   *    TRUE if the update must be performed.
   */
  public function isAvailableUpdate($update_type, $update_states, AdvancedUpdateEntity $advanced_update_entity) {
    $is_available = FALSE;
    $module_name = $advanced_update_entity->moduleName();

    // If at least one update was performed.
    if (!empty($update_states[$module_name])) {
      if ($update_type === self::DOWN) {
        // If it's a downgrade.
        if ($advanced_update_entity->date() <= $update_states[$module_name]) {
          $is_available = TRUE;
        }
      }
      else {
        // If it's an update.
        if ($advanced_update_entity->date() > $update_states[$module_name]) {
          $is_available = TRUE;
        }
      }
    }
    elseif ($update_type === self::UP) {
      $is_available = TRUE;
    }
    return $is_available;
  }

  /**
   * Get and set data and Advanced Updates.
   *
   * @param string $update_type
   *    self::UP or self::DOWN.
   * @param string $module
   *    Filter by module name.
   * @param int $update_deep_number
   *    Number of updates to perform for a module.
   */
  protected function prepareData($update_type, $module = NULL, $update_deep_number = NULL) {
    // Set data and get needed data.
    $this->setSelectedModule($module);
    $this->setUpdateType($update_type);
    $this->setDeepNumber($update_deep_number);
    $this->getUpdateState();

    // Get all Advanced Updates availables.
    $this->getAdvancedUpdates();

    // Filter Advanced updates by module in order to match
    // with the current use case.
    $this->updatesSlicing();
  }

  /**
   * Get a status of updates. Do not apply updates.
   *
   * @param string $update_type
   *    self:UP or self:DOWN.
   * @param string $module
   *    A module name in order to filter results.
   * @param string $update_deep_number
   *    Number of updates to apply. Used only if a module name was specified.
   *
   * @return array
   *    list of AdvancedUpdateStatus related to wanted parameters.
   */
  public function getReport($update_type, $module = NULL, $update_deep_number = NULL) {
    $this->prepareData($update_type, $module, $update_deep_number);

    $callback = function ($returned, $update) {
      $returned[] = new AdvancedUpdateStatus($update);
      return $returned;
    };
    return array_reduce($this->advancedUpdates, $callback, array());
  }

  /**
   * Apply all updates found.
   *
   * @param string $update_type
   *    self:UP or self:DOWN.
   * @param string $module
   *    A module name in order to filter results.
   * @param string $update_deep_number
   *    Used only if a module name was specified.
   *
   * @return array
   *    A list of AdvancedUpdateStatus sorted by module.
   */
  public function execute($update_type, $module = NULL, $update_deep_number = NULL) {
    $this->prepareData($update_type, $module, $update_deep_number);
    $this->applyUpdates();
    return $this->updateStatus;
  }

}
