<?php

namespace Drupal\config_override_warn;

use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormInterface;

/**
 * Contains logic for inspecting config forms and their overridden values.
 */
class FormOverrides {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs the FormOverrides service.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager) {
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;
    $this->config = $this->configFactory->get('config_override_warn.settings');
  }

  /**
   * Get overrides for a form.
   *
   * @param FormInterface $form
   *   The form object.
   * @return array
   *   A nested array of overridden config values, keyed by config name, and subkeyed by config value name.
   *   The subvalue is an array with 'original' and 'override' values of the respective config property.
   */
  public function getFormOverrides(FormInterface $form) {
    $names = $this->getFormConfigNames($form);
    $overrides = [];
    foreach ($names as $name) {
      $overrides = NestedArray::mergeDeep($overrides, $this->getConfigOverrideDiffs($name));
    }
    return $overrides;
  }

  /**
   * Get the config names that correspond with a form.
   *
   * @param FormInterface $form
   *   The form object.
   *
   * @return array
   *   An array of config names.
   */
  public function getFormConfigNames(FormInterface $form) {
    $names = [];
    if ($form instanceof EntityForm) {
      $entity = $form->getEntity();
      if ($entity instanceof ConfigEntityInterface && !$entity->isNew()) {
        $names = [$entity->getConfigDependencyName()];
      }
    }
    elseif (method_exists($form, 'getEditableConfigNames')) {
      // Grr... this is a protected method on \Drupal\Core\Form\ConfigFormBaseTrait
      // @see https://www.drupal.org/project/drupal/issues/2095289
      $method = new \ReflectionMethod($form, 'getEditableConfigNames');
      $method->setAccessible(TRUE);
      $names = $method->invoke($form);
    }
    return $names;
  }

  /**
   * Get overrides for a config.
   *
   * @param $name
   *   The name of the configuration object
   *
   * @return array
   *   A nested array of overridden config values, keyed by config value name.
   *   The value is an array with 'original' and 'override' values of the respective config property.
   */
  public function getConfigOverrideDiffs($name) {
    $overrides = [];
    $config = $this->configFactory->get($name);

    if ($config_overrides = $this->getConfigOverrides($config)) {
      $definition = $this->typedConfigManager->getDefinition($name);
      $keys = $this->getConfigKeys($config_overrides, $definition);
      foreach ($keys as $key) {
        $original_value = $config->getOriginal($key, FALSE);
        $override_value = $config->get($key);

        // If both values are an array, run a diff on them to reduce same values.
        // @todo Remove when https://www.drupal.org/project/config_override_warn/issues/2979946 is fixed.
        if (is_array($original_value) && is_array($override_value)) {
          $original_original_value = $original_value;
          $original_value = DiffArray::diffAssocRecursive($original_value, $override_value);
          $override_value = DiffArray::diffAssocRecursive($override_value, $original_original_value);
        }

        if ($this->config->get('show_values')) {
          $overrides[$name][$key] = [
            'original' => var_export($original_value, TRUE),
            'override' => var_export($override_value, TRUE),
          ];
        }
        else {
          $overrides[$name][$key] = NULL;
        }
      }
    }
    return $overrides;
  }

  /**
   * Get all overridden values from a config object.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   *
   * @return array
   *   A nested array of the overridden values on the config.
   */
  public function getConfigOverrides(Config $config) {
    // The hasOverrides method only exists in Drupal 8.5+, so shortcut if it returns false.
    if (method_exists($config, 'hasOverrides') && !$config->hasOverrides()) {
      return [];
    }

    $overrides = [];
    $properties = ['moduleOverrides', 'settingsOverrides'];
    foreach ($properties as $property) {
      $reflection = new \ReflectionProperty($config, $property);
      $reflection->setAccessible(TRUE);
      $property_overrides = $reflection->getValue($config);
      if (isset($property_overrides) && is_array($property_overrides)) {
        $overrides = NestedArray::mergeDeepArray([$overrides, $property_overrides], TRUE);
      }
    }
    return $overrides;
  }

  /**
   * Get all possible keys from a config object.
   *
   * @param array $values
   *   The root values from the config object.
   * @param array $definition
   *   The config definition for $values.
   * @param null $prefix
   *   Used for recursion of sub-keys.
   *
   * @return array
   *   An array of config keys.
   */
  protected function getConfigKeys(array $values, array $definition, $prefix = NULL) {
    $keys = [];
    foreach ($values as $key => $value) {
      if (is_array($value) && isset($definition['mapping'])) {
        $value_definition = NestedArray::getValue($definition['mapping'], explode('.', $key));
        if (isset($value_definition['type']) && $value_definition['type'] === 'mapping') {
          $keys = array_merge($keys, $this->getConfigKeys($value, $value_definition, $prefix . $key . '.'));
          continue;
        }
      }
      $keys[] = $prefix . $key;
    }
    return $keys;
  }

}
