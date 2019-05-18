<?php

namespace Drupal\fapi_validation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for Fapi Validaton Filters Plugin.
 */
class FapiValidationFiltersManager extends DefaultPluginManager {

  /**
   * Constructs a MessageManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('fapi_validation_filters_info');
    $this->setCacheBackend($cache_backend, 'fapi_validation_filters');

    parent::__construct(
      'Plugin/FapiValidationFilter',
      $namespaces,
      $module_handler,
      'Drupal\fapi_validation\FapiValidationFiltersInterface',
      'Drupal\fapi_validation\Annotation\FapiValidationFilter'
    );
  }

  /**
   * Check if Filter Plugin exists.
   *
   * @param string $id
   *   Validators Name.
   *
   * @return bool
   *   Check.
   */
  public function hasFilter($id) {
    // var_dump($this->getDefinitions()); exit;.
    return in_array($id, array_keys($this->getDefinitions()));
  }

  /**
   * Execute filter.
   *
   * @param array &$element
   *   Form Element.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   Form State Object.
   */
  public function filter(array &$element, FormStateInterface &$form_state) {
    $def = $element['#filters'];

    foreach ($def as $filter_name) {
      if (!$this->hasFilter($filter_name)) {
        // @TODO throw Validator not found
        throw new \LogicException("Invalid filter name '{$filter_name}'.");
      }

      $plugin = $this->getDefinition($filter_name);
      $instance = $this->createInstance($plugin['id']);

      $current_value = $form_state->getValue($element['#parents']);

      $new_value = $instance->filter($current_value);
      $element['#value'] = $new_value;
      $form_state->setValue($element['#parents'], $new_value);
    }
  }

}
