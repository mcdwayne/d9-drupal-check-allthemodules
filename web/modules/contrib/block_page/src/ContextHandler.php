<?php

/**
 * @file
 * Contains \Drupal\block_page\ContextHandler.
 */

namespace Drupal\block_page;

use Drupal\Component\Plugin\Context\ContextInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Provides methods to handle sets of contexts.
 */
class ContextHandler {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs a new ContextHandler.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data
   *   The typed data manager.
   */
  public function __construct(TypedDataManager $typed_data) {
    $this->typedDataManager = $typed_data;
  }

  /**
   * Checks a set of requirements against a set of contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $requirements
   *   An array of requirements.
   *
   * @return bool
   *   TRUE if all of the requirements are satisfied by the context, FALSE
   *   otherwise.
   */
  public function checkRequirements(array $contexts, array $requirements) {
    foreach ($requirements as $requirement) {
      if ($requirement->isRequired() && !$this->getValidContexts($contexts, $requirement)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Determines plugins whose constraints are satisfied by a set of contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager.
   *
   * @return array
   *   An array of plugin definitions.
   */
  public function getAvailablePlugins(array $contexts, PluginManagerInterface $manager) {
    return array_filter($manager->getDefinitions(), function ($plugin_definition) use ($contexts) {
      // If this plugin doesn't need any context, it is available to use.
      if (!isset($plugin_definition['context'])) {
        return TRUE;
      }

      // Build an array of requirements out of the contexts specified by the
      // plugin definition.
      $requirements = array();
      foreach ($plugin_definition['context'] as $context_id => $plugin_context) {
        $definition = $this->typedDataManager->getDefinition($plugin_context['type']);
        $definition['type'] = $plugin_context['type'];

        // If the plugin specifies additional constraints, add them to the
        // constraints defined by the plugin type.
        if (isset($plugin_context['constraints'])) {
          // Ensure the array exists before adding in constraints.
          if (!isset($definition['constraints'])) {
            $definition['constraints'] = array();
          }

          $definition['constraints'] += $plugin_context['constraints'];
        }

        // Assume the requirement is required if unspecified.
        if (!isset($definition['required'])) {
          $definition['required'] = TRUE;
        }

        $requirements[$context_id] = new DataDefinition($definition);
      }

      // Check the set of contexts against the requirements.
      return $this->checkRequirements($contexts, $requirements);
    });
  }

  /**
   * Determines which contexts satisfy the constraints of a given definition.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The definition to satisfy.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of valid contexts.
   */
  public function getValidContexts(array $contexts, DataDefinitionInterface $definition) {
    return array_filter($contexts, function (ContextInterface $context) use ($definition) {
      // @todo getContextDefinition() should return a DataDefinitionInterface.
      $context_definition = new DataDefinition($context->getContextDefinition());

      // If the data types do not match, this context is invalid.
      if ($definition->getDataType() != $context_definition->getDataType()) {
        return FALSE;
      }

      // If any constraint does not match, this context is invalid.
      foreach ($definition->getConstraints() as $constraint_name => $constraint) {
        if ($context_definition->getConstraint($constraint_name) != $constraint) {
          return FALSE;
        }
      }

      // All contexts with matching data type and contexts are valid.
      return TRUE;
    });
  }

}
