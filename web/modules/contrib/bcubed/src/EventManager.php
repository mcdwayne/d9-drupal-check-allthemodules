<?php

namespace Drupal\bcubed;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Bcubed Event plugin manager.
 */
class EventManager extends DefaultPluginManager {

  /**
   * Constructs an EventManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\bcubed\StringGenerator $string_generator
   *   String Generator object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, StringGenerator $string_generator) {
    parent::__construct('Plugin/bcubed/Event', $namespaces, $module_handler, 'Drupal\bcubed\EventInterface', 'Drupal\bcubed\Annotation\Event');

    $this->alterInfo('bcubed_events_info');
    $this->setCacheBackend($cache_backend, 'bcubed_events');
    $this->stringGenerator = $string_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // Fetch generated strings and merge into configuration.
    $definition = $this->getDefinition($plugin_id);

    $dictionary = !empty($definition['generated_strings_dictionary']) ? $definition['generated_strings_dictionary'] : $plugin_id;

    $strings = $this->stringGenerator->getStrings($dictionary);
    if (empty($strings) && !empty($definition['generated_strings'])) {
      $this->stringGenerator->registerDictionary($dictionary, $definition['generated_strings']);
      $strings = $this->stringGenerator->getStrings($dictionary);
    }
    $configuration['generated_strings'] = $strings;

    return parent::createInstance($plugin_id, $configuration);
  }

}
