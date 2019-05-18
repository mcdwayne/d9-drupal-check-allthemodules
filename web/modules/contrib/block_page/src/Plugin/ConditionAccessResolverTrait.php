<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\ConditionAccessResolverTrait.
 */

namespace Drupal\block_page\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Condition\ConditionInterface;

/**
 * Resolves a set of conditions.
 */
trait ConditionAccessResolverTrait {

  /**
   * Resolves the given conditions based on the condition logic ('and'/'or').
   *
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   A set of conditions.
   * @param string $condition_logic
   *   The logic used to compute access, either 'and' or 'or'.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An array of contexts to set on the conditions.
   *
   * @return bool
   *   Whether these conditions grant or deny access.
   */
  protected function resolveConditions($conditions, $condition_logic, $contexts = array()) {
    foreach ($conditions as $condition) {
      $this->prepareCondition($condition, $contexts);

      try {
        $pass = $condition->execute();
      }
      catch (PluginException $e) {
        // If a condition is missing context, consider that a fail.
        $pass = FALSE;
      }

      // If a condition fails and all conditions were required, deny access.
      if (!$pass && $condition_logic == 'and') {
        return FALSE;
      }
      // If a condition passes and one condition was required, grant access.
      elseif ($pass && $condition_logic == 'or') {
        return TRUE;
      }
    }

    // If no conditions passed and one condition was required, deny access.
    return $condition_logic == 'and';
  }

  /**
   * Prepares a condition for evaluation.
   *
   * @param \Drupal\Core\Condition\ConditionInterface $condition
   *   A condition about to be evaluated.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts to set on the condition.
   */
  protected function prepareCondition(ConditionInterface $condition, $contexts) {
    if ($condition instanceof ContextAwarePluginInterface) {
      // @todo Find a better way to handle unwanted context.
      $condition_contexts = $condition->getContextDefinitions();

      // @todo Find a better way to load context assignments.
      $configuration = $condition->getConfiguration();
      $assignments = isset($configuration['context_assignments']) ? array_flip($configuration['context_assignments']) : array();

      foreach ($contexts as $name => $context) {
        // If this context was given a specific name, use that.
        $name = isset($assignments[$name]) ? $assignments[$name] : $name;
        if (isset($condition_contexts[$name])) {
          $condition->setContextValue($name, $context->getContextValue());
        }
      }
    }
  }

}
