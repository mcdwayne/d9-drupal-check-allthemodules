<?php

namespace Drupal\fapi_validation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for Fapi Validaton Validators Plugin.
 */
class FapiValidationValidatorsManager extends DefaultPluginManager {

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
    $this->alterInfo('fapi_validation_validators_info');
    $this->setCacheBackend($cache_backend, 'fapi_validation_validators');

    parent::__construct(
      'Plugin/FapiValidationValidator',
      $namespaces,
      $module_handler,
      'Drupal\fapi_validation\FapiValidationValidatorsInterface',
      'Drupal\fapi_validation\Annotation\FapiValidationValidator'
    );
  }

  /**
   * Check if Valildator Plugin exists.
   *
   * @param string $id
   *   Validators Name.
   *
   * @return bool
   *   Check
   */
  public function hasValidator($id) {
    return in_array($id, array_keys($this->getDefinitions()));
  }

  /**
   * Execute validation.
   *
   * @param array &$element
   *   Form Element.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   Form State Object.
   */
  public function validate(array &$element, FormStateInterface &$form_state) {
    // If element is empty and not required, by pass rule validation.
    if (!$element['#required'] && empty($element['#value']) && $element['#value'] !== 0) {
      return;
    }

    $def = $element['#validators'];

    foreach ($def as $raw_validation) {
      // Parse Validator.
      $validator = new Validator($raw_validation, $form_state->getValue($element['#parents']));

      if (!$this->hasValidator($validator->getName())) {
        // @TODO throw Validator not found
        throw new \LogicException("Invalid validator name '{$validator->getName()}'.");
      }

      $plugin = $this->getDefinition($validator->getName());
      $instance = $this->createInstance($plugin['id']);

      if (!$instance->validate($validator, $element, $form_state)) {
        $error_message = $this->processErrorMessage($validator, $plugin, $element);
        $form_state->setError($element, $error_message);
      }
    }
  }

  /**
   * Process Error Message.
   *
   * @param \Drupal\fapi_validation\Validator $validator
   *   Validator.
   * @param array $plugin
   *   Plugin data.
   * @param array $element
   *   Form Element.
   *
   * @return string
   *   Error messaage.
   */
  protected function processErrorMessage(Validator $validator, array $plugin, array $element) {
    // User defined error callback?
    if ($validator->hasErrorCallbackDefined()) {
      return call_user_func_array($validator->getErrorCallback(), [$validator, $element]);
    }
    // User defined error message?
    elseif ($validator->hasErrorMessageDefined()) {
      $message = $validator->getErrorMessage();
    }
    // Plugin defined error callback?
    elseif ($plugin['error_callback'] !== NULL) {
      return call_user_func_array([$plugin['class'], $plugin['error_callback']], [$validator, $element]);
    }
    // Plugin defined error message?
    elseif ($plugin['error_message'] !== NULL) {
      $message = $plugin['error_message'];
    }
    else {
      $message = "Unespecified validator error message for field %field.";
    }

    return \t($message, ['%field' => $element['#title']]);
  }

}
