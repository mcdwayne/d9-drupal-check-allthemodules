<?php

namespace Drupal\bcubed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Condition Set entity.
 *
 * @ConfigEntityType(
 *   id = "condition_set",
 *   label = @Translation("Condition Set"),
 *   handlers = {
 *     "list_builder" = "Drupal\bcubed\ConditionSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bcubed\Form\ConditionSetForm",
 *       "edit" = "Drupal\bcubed\Form\ConditionSetForm",
 *       "delete" = "Drupal\bcubed\Form\ConditionSetDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\bcubed\ConditionSetHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "condition_set",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/bcubed/condition_set/{condition_set}",
 *     "add-form" = "/admin/config/system/bcubed/condition_set/add",
 *     "edit-form" = "/admin/config/system/bcubed/condition_set/{condition_set}/edit",
 *     "delete-form" = "/admin/config/system/bcubed/condition_set/{condition_set}/delete",
 *     "collection" = "/admin/config/system/bcubed/condition_set"
 *   }
 * )
 */
class ConditionSet extends ConfigEntityBase implements ConditionSetInterface {

  /**
   * The Condition Set ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Condition Set label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Condition Set weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The Condition Set status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The Condition Set description.
   *
   * @var string
   */
  protected $description;

  /**
   * Configured events.
   *
   * @var array
   */
  protected $events;

  /**
   * Configured conditions.
   *
   * @var array
   */
  protected $conditions;

  /**
   * Configured actions.
   *
   * @var array
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsPlugins() {
    $plugins = [];

    $items = [
      [
        'manager' => \Drupal::service('plugin.manager.bcubed.event'),
        'config' => $this->events,
      ],
      [
        'manager' => \Drupal::service('plugin.manager.bcubed.condition'),
        'config' => $this->conditions,
      ],
      [
        'manager' => \Drupal::service('plugin.manager.bcubed.action'),
        'config' => $this->actions,
      ],
    ];

    foreach ($items as $item) {
      foreach ($item['config'] as $config) {
        try {
          // Fetch plugin instance.
          if (!empty($config['data'])) {
            // Create pre-configured instance.
            $plugin = $item['manager']->createInstance($config['id'], ['settings' => $config['data']]);
          }
          else {
            // Create instance without configuration.
            $plugin = $item['manager']->createInstance($config['id']);
          }
          // Get library.
          $lib = $plugin->getLibrary();
          if (!empty($lib) && !in_array($lib, $plugins)) {
            $plugins[] = $lib;
          }
        }
        catch (PluginNotFoundException $e) {
          // Ignore missing plugin.
        }
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $eventdefs = \Drupal::service('plugin.manager.bcubed.event')->getDefinitions();
    $conditiondefs = \Drupal::service('plugin.manager.bcubed.condition')->getDefinitions();
    $actiondefs = \Drupal::service('plugin.manager.bcubed.action')->getDefinitions();

    $modules = [];

    // Calculate plugin providers.
    foreach ($this->events as $event) {
      if (!in_array($eventdefs[$event['id']]['provider'], $modules)) {
        $modules[] = $eventdefs[$event['id']]['provider'];
      }
    }

    foreach ($this->actions as $action) {
      if (!in_array($actiondefs[$action['id']]['provider'], $modules)) {
        $modules[] = $actiondefs[$action['id']]['provider'];
      }
    }

    foreach ($this->conditions as $condition) {
      if (!in_array($conditiondefs[$condition['id']]['provider'], $modules)) {
        $modules[] = $conditiondefs[$condition['id']]['provider'];
      }
      // Add config dependency for originating condition set.
      if ($condition['id'] == 'originating_condition_set') {
        $this->addDependency('config', 'bcubed.condition_set.' . $condition['data']['condition_set']);
      }
    }

    foreach ($modules as $module) {
      $this->addDependency('module', $module);
    }

    return $this;
  }

}
