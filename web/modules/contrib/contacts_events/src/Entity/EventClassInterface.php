<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\rules\Context\ContextConfig;

/**
 * Provides an interface for defining Class entities.
 */
interface EventClassInterface extends ConfigEntityInterface {

  /**
   * Add a condition.
   *
   * @param string $condition_id
   *   The condition ID.
   * @param \Drupal\rules\Context\ContextConfig|null $config
   *   The condition configuration.
   *
   * @return $this
   */
  public function addCondition($condition_id, ContextConfig $config = NULL);

  /**
   * Add a context definition.
   *
   * @param string $name
   *   The context name.
   * @param array|\Drupal\rules\Context\ContextDefinitionInterface $definition
   *   Either a context definition or it's configuration array.
   *
   * @return $this
   */
  public function addContext($name, $definition);

  /**
   * Evaluate the condition.
   *
   * @param array $contexts
   *   An array of context values keyed by context name.
   *
   * @return bool
   *   Whether the class conditions match the given context.
   */
  public function evaluate(array $contexts);

}
