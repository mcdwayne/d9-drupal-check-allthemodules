<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Condition\ConditionPluginCollection;

/**
 * Provides a trait for making a config entity with conditions.
 */
trait ConfigEntityConditionTrait {

  /**
   * The conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The condition operator.
   *
   * @var string
   */
  protected $conditionOperator = 'AND';

  /**
   * The collection of conditions.
   *
   * @var array
   */
  protected $conditionCollection = [];

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    if (empty($this->conditionCollection)) {
      $this->conditionCollection = new ConditionPluginCollection($this->conditionPluginManager(), $this->get('conditions'));
    }
    return $this->conditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition($instance_id) {
    return $this->getConditions()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setCondition($instance_id, array $configuration) {
    if (!$this->getConditions()->has($instance_id)) {
      $configuration['id'] = $instance_id;
      $this->getConditions()->addInstanceId($instance_id, $configuration);
    }
    else {
      $this->getConditions()->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionOperator() {
    return $this->conditionOperator;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionOperator($condition_operator) {
    if (in_array($condition_operator, ['AND', 'OR'])) {
      $this->conditionOperator = $condition_operator;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'conditions' => $this->getConditions(),
    ];
  }

}
