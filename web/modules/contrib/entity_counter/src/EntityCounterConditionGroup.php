<?php

namespace Drupal\entity_counter;

use Drupal\Core\Entity\EntityInterface;

/**
 * Represents an entity counter condition group.
 */
class EntityCounterConditionGroup {

  /**
   * The entity counter conditions.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterConditionInterface[]
   */
  protected $conditions;

  /**
   * The operator.
   *
   * Possible values: AND, OR.
   *
   * @var string
   */
  protected $operator;

  /**
   * Constructs a new EntityCounterConditionGroup object.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterConditionInterface[] $conditions
   *   The entity counter conditions.
   * @param string $operator
   *   The operator. Possible values: AND, OR.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid operator is given.
   */
  public function __construct(array $conditions, $operator) {
    if (!in_array($operator, ['AND', 'OR'])) {
      throw new \InvalidArgumentException(sprintf('Invalid operator "%s" given, expecting "AND" or "OR".', $operator));
    }

    $this->conditions = $conditions;
    $this->operator = $operator;
  }

  /**
   * Gets the entity counter conditions.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterConditionInterface[]
   *   The entity counter conditions.
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * Gets the operator.
   *
   * @return string
   *   The operator. Possible values: AND, OR.
   */
  public function getOperator() {
    return $this->operator;
  }

  /**
   * Evaluates the entity counter condition group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity counter condition group has passed, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity) {
    if (empty($this->conditions)) {
      return TRUE;
    }

    $boolean = $this->operator == 'AND' ? FALSE : TRUE;
    foreach ($this->conditions as $condition) {
      if ($condition->evaluate($entity) == $boolean) {
        return $boolean;
      }
    }

    return !$boolean;
  }

}
