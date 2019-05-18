<?php

namespace Drupal\commerce_inventory\Element;

use Drupal\commerce_inventory\InventoryHelper;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Provides an Commerce Inventory Remote Id autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("commerce_inventory_remote_id")
 */
class InventoryRemoteId extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#provider'] = NULL;
    $info['#provider_settings'] = [];
    $info['#tags'] = FALSE;

    array_unshift($info['#process'], [$class, 'processRemoteIdAutocomplete']);
    return $info;
  }


  /**
   * Form element validation handler for entity_autocomplete elements.
   */
  public static function validateRemoteIdAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value'])) {
      if (is_array($element['#value'])) {
        $input_values = $element['#value'];
      }
      else {
        $input_values = $element['#tags'] ? Tags::explode($element['#value']) : [$element['#value']];
      }

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $element['#provider_settings']['entity'];
      $provider_type = $element['#provider_settings']['provider'];
      $contexts = InventoryHelper::buildContexts([
        $entity->getEntityTypeId() => $entity
      ]);

      /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface $provider */
      $provider = \Drupal::service('plugin.manager.commerce_inventory_provider')->createInstance($provider_type);

      $invalid_ids = [];
      foreach ($input_values as $input_value) {
        if (!$provider->validateRemoteId($input_value, $entity->getEntityTypeId(), $contexts)) {
          $invalid_ids[] = $input_value;
        }
      }

      if (!empty($invalid_ids)) {
        $message = new PluralTranslatableMarkup(count($invalid_ids), 'This ID is invalid.', 'The following IDs are invalid: "%ids".', [
          '%ids' => implode('", "', $invalid_ids)
        ]);
        $form_state->setError($element, $message);
      }

    }
  }

  /**
   * Adds remote id autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #provider: The ID of the target provider type.
   *   - #provider_settings: An array of settings that will be passed to the
   *     autocomplete route.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #provider is missing.
   */
  public static function processRemoteIdAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#provider'])) {
      throw new \InvalidArgumentException('Missing required #provider parameter.');
    }

    // Store the provider settings in the key/value store and pass a hashed key
    // in the route parameters.
    $provider_settings = isset($element['#provider_settings']) ? $element['#provider_settings'] : [];
    $data = serialize($provider_settings) . $element['#provider'];
    $provider_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('commerce_inventory_remote_id_autocomplete');
    if (!$key_value_storage->has($provider_settings_key)) {
      $key_value_storage->set($provider_settings_key, $provider_settings);
    }

    $element['#autocomplete_route_name'] = 'commerce_inventory.remote_id_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'provider' => $element['#provider'],
      'provider_settings_key' => $provider_settings_key,
    ];
    array_unshift($element['#element_validate'], [self::class, 'validateRemoteIdAutocomplete']);


    return $element;
  }

}
