<?php

namespace Drupal\smart_content_segments\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\Entity\SmartVariationSet;

/**
 * Defines the Smart segment entity.
 *
 * @ConfigEntityType(
 *   id = "smart_segment",
 *   label = @Translation("Smart segment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\smart_content_segments\SmartSegmentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\smart_content_segments\Form\SmartSegmentForm",
 *       "edit" = "Drupal\smart_content_segments\Form\SmartSegmentForm",
 *       "delete" = "Drupal\smart_content_segments\Form\SmartSegmentDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\smart_content_segments\SmartSegmentHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "smart_segment",
 *   admin_permission = "administer segments",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/smart_segment/{smart_segment}",
 *     "add-form" = "/admin/structure/smart_segment/add",
 *     "edit-form" = "/admin/structure/smart_segment/{smart_segment}/edit",
 *     "delete-form" = "/admin/structure/smart_segment/{smart_segment}/delete",
 *     "collection" = "/admin/structure/smart_segment"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "conditions_settings",
 *   },
 * )
 */
class SmartSegment extends ConfigEntityBase implements SmartSegmentInterface {

  /**
   * The Smart segment ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Smart segment label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Smart segment label.
   *
   * @var string
   */
  protected $conditions;

  /**
   * @param \Drupal\smart_content\Condition\ConditionInterface $condition
   */
  public function addCondition(ConditionInterface $condition) {
    if ($condition->id() === NULL) {
      $condition->setId(SmartVariationSet::generateUniquePluginId($condition, array_keys($this->getConditions())));
    }

    //@todo: find better way to do this.
    $condition->parentInstance = $this;
    $condition->entity = $this;
    $this->conditions[$condition->id()] = $condition;

  }

  /**
   * @return array
   */
  public function getConditions() {
    if (!isset($this->conditions)) {
      $this->conditions = [];
      foreach ($this->getConditionsFromSettings() as $plugin) {
        $plugin->parentInstance = $this;
        $plugin->entity = $this;
        $this->addCondition($plugin);
      }
    }
    return $this->conditions;
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  public function getCondition($id) {
    foreach ($this->getConditions() as $condition) {
      if ($condition->id() == $id) {
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


  protected function getConditionsFromSettings() {
    $plugins = [];
    if (!empty($this->conditions_settings)) {
      foreach ($this->conditions_settings as $id => $value) {
        $plugins[] = \Drupal::getContainer()
          ->get('plugin.manager.smart_content.condition')
          ->createInstance($value['plugin_id'], $value);
      }
    }
    return $plugins;
  }


  public function sortConditions() {
    if ($this->getConditions()) {
      uasort($this->conditions, function ($first, $second) {
        return $first->getWeight() > $second->getWeight();
      });
    }
  }

  public function getAttachedSettings() {
    $condition_settings = [];
    foreach ($this->getConditions() as $condition) {
      $condition_settings[] = $condition->getAttachedSettings();
    }
    return [
      'id' => $this->id(),
      'conditions' => $condition_settings,
    ];
  }


  public function writeChangesToSettings() {
    //    $configuration = $this->getConfiguration();
    $this->conditions_settings = [];
    foreach ($this->getConditions() as $condition) {
      $condition->writeChangesToConfiguration();
      $this->conditions_settings[] = $condition->getConfiguration();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->writeChangesToSettings();
    $conditionManager = \Drupal::service('plugin.manager.smart_content.condition');
    $conditionManager->clearCachedDefinitions();
    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $conditionManager = \Drupal::service('plugin.manager.smart_content.condition');
    $conditionManager->clearCachedDefinitions();
  }

  /**
   * Get decision agent.
   *
   * @return \Drupal\smart_content\DecisionAgent\DecisionAgentInterface
   */
  function getDecisionAgent() {
    if (!isset($this->decisionAgentInstance)) {
      $this->decisionAgentInstance = \Drupal::service('plugin.manager.smart_content.decision_agent')
        ->createInstance('client', [], $this);
    }
    return $this->decisionAgentInstance;
  }

}
