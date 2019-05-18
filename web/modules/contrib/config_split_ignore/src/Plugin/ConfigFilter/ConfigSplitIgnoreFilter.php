<?php

namespace Drupal\config_split_ignore\Plugin\ConfigFilter;

use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a ignore filter that allows to delete the configuration entities.
 *
 * @ConfigFilter(
 *   id = "config_split_ignore",
 *   label = "Configuration Split Ignore",
 *   weight = 20
 * )
 */
class ConfigSplitIgnoreFilter extends IgnoreFilter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $container->get('entity_type.manager')->getStorage('config_split');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    // Load the list of ignored entities from enabled splits.
    $ignored = [];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $config_split */
    foreach ($entity_storage->loadMultiple() as $config_split) {
      $config_name = $config_split->getConfigDependencyName();
      $config = $config_factory->get($config_name);
      if (!empty($config->get('status'))) {
        $ignored = array_merge($ignored, $config_split->getThirdPartySetting('config_split_ignore', 'entities', []));
      }
    }

    $ignored = array_unique($ignored);

    // Allow modules to alter the list of ignored entities.
    $container->get('module_handler')->invokeAll('config_split_ignore_settings_alter', [&$ignored]);

    // Set the list in the plugin configuration.
    $configuration['ignored'] = array_unique($ignored);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function matchConfigName($config_name) {
    if (Settings::get('config_split_ignore_deactivate')) {
      // Allow deactivating config_split_ignore in settings.php.
      // Do not match any name in that case and allow
      // a normal configuration import to happen.
      return FALSE;
    }

    // If the string is an excluded config, don't ignore it.
    if (in_array(static::FORCE_EXCLUSION_PREFIX . $config_name, $this->configuration['ignored'], TRUE)) {
      return FALSE;
    }

    foreach ($this->configuration['ignored'] as $config_ignore_setting) {
      // Split the ignore settings so that we can ignore individual keys.
      $ignore = explode(':', $config_ignore_setting);
      if (fnmatch($ignore[0], $config_name)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function activeRead($name, $data) {
    $keys = [];
    foreach ($this->configuration['ignored'] as $ignored) {
      // Split the ignore settings so that we can ignore individual keys.
      $ignored = explode(':', $ignored);
      if (fnmatch($ignored[0], $name)) {
        if (count($ignored) == 1) {
          // If one of the definitions does not have keys ignore the
          // whole config. If the active configuration doesn't exist,
          // allow to create it.
          $active = $this->active->read($name);
          return $active ? $active : $data;
        }
        else {
          // Add the sub parts to ignore to the keys.
          $keys[] = $ignored[1];
        }
      }

    }

    $active = $this->active->read($name);
    if (!$active) {
      return $data;
    }
    foreach ($keys as $key) {
      $parts = explode('.', $key);

      if (count($parts) == 1) {
        if (isset($active[$key])) {
          $data[$key] = $active[$key];
        }
      }
      else {
        $key_exists = FALSE;
        $value = NestedArray::getValue($active, $parts, $key_exists);
        if ($key_exists) {
          // Enforce the value if it existed in the active config.
          NestedArray::setValue($data, $parts, $value, TRUE);
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // The ignored configuration entity must exist in a file in
    // config split folder in order to be deleted properly.
    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    // Allow to delete the configuration if the split becomes inactive.
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDeleteAll($prefix, $delete) {
    // Support export time ignoring.
    return !empty($this->configuration['ignored']) ? FALSE : $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    return $collections;
  }

  /**
   * Returns the list of ignored configuration names with their ignored keys.
   */
  public function getIgnoredKeys() {
    if (empty($this->configuration['ignored'])) {
      return [];
    }

    $active_names = $this->active->listAll();
    $ignored_keys = [];

    foreach ($this->configuration['ignored'] as $config_ignore_setting) {
      $ignore = explode(':', $config_ignore_setting);
      $ignore_name_pattern = $ignore[0];
      $ignore_key = isset($ignore[1]) ? $ignore[1] : '';

      foreach ($active_names as $config_name) {
        if ($config_ignore_setting === (static::FORCE_EXCLUSION_PREFIX . $config_name)) {
          continue;
        }

        if (fnmatch($ignore_name_pattern, $config_name)) {
          $ignored_keys[$config_name][$ignore_key] = TRUE;
        }
      }
    }

    foreach (array_keys($ignored_keys) as $ignore_name) {
      if (isset($ignored_keys[$ignore_name][''])) {
        // The whole configuration entity is ignored.
        $ignored_keys[$ignore_name] = [];
      }
      else {
        // Just some keys are ignored.
        $ignored_keys[$ignore_name] = array_keys($ignored_keys[$ignore_name]);
      }
    }

    return $ignored_keys;
  }

}
