<?php

namespace Drupal\snippet_manager;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for snippet variables.
 */
abstract class SnippetVariableBase extends PluginBase implements SnippetVariableInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->t('String');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => $this->t('This plugin has no configurable options.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete() {

  }

  /**
   * Calculates and returns dependencies of a specific plugin instance.
   *
   * PluginDependencyTrait::getPluginDependencies() has been added in
   * Drupal 8.6. So we have to keep own implementation to preserve compatibility
   * with earlier Drupal versions.
   *
   * @see \Drupal\Core\Plugin\PluginDependencyTrait::getPluginDependencies()
   * @todo Remove this once we drop support of Drupal 8.5.
   */
  protected function getPluginDependencies(PluginInspectionInterface $instance) {
    $dependencies = [];
    $definition = $instance->getPluginDefinition();
    if ($definition instanceof PluginDefinitionInterface) {
      $dependencies['module'][] = $definition->getProvider();
      if ($definition instanceof DependentPluginDefinitionInterface && $config_dependencies = $definition->getConfigDependencies()) {
        $dependencies = NestedArray::mergeDeep($dependencies, $config_dependencies);
      }
    }
    elseif (is_array($definition)) {
      $dependencies['module'][] = $definition['provider'];
      // Plugins can declare additional dependencies in their definition.
      if (isset($definition['config_dependencies'])) {
        $dependencies = NestedArray::mergeDeep($dependencies, $definition['config_dependencies']);
      }
    }

    // If a plugin is dependent, calculate its dependencies.
    if ($instance instanceof DependentPluginInterface && $plugin_dependencies = $instance->calculateDependencies()) {
      $dependencies = NestedArray::mergeDeep($dependencies, $plugin_dependencies);
    }
    return $dependencies;
  }

}
