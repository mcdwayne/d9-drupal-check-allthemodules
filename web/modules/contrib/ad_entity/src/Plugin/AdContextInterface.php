<?php

namespace Drupal\ad_entity\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for Advertising context plugins.
 */
interface AdContextInterface extends PluginInspectionInterface {

  /**
   * Returns the form elements for the context settings.
   *
   * @param array $settings
   *   Current values of the context settings.
   * @param array $form
   *   The form where the context is being configured.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding form state.
   *
   * @return array
   *   The settings as form array.
   */
  public function settingsForm(array $settings, array $form, FormStateInterface $form_state);

  /**
   * Massages the form values of the settings into a proper storage format.
   *
   * The settings must represent a JSON-compatible data structure,
   * since these will be used as settings output for the context itself.
   *
   * @param array $settings
   *   The submitted form values of the context settings.
   *
   * @return array
   *   The context settings, ready to be saved on the storage.
   */
  public function massageSettings(array $settings);

  /**
   * Handles proper JSON encoding on the given context data.
   *
   * @param array $context_data
   *   The context data array to serialize.
   *
   * @return string
   *   A JSON-encoded string representation of the context data.
   */
  public static function getJsonEncode(array $context_data);

  /**
   * Handles proper JSON decoding on the given context data.
   *
   * @param string $context_data
   *   The JSON-encoded context data to deserialize.
   *
   * @return array|null
   *   The decoded context data.
   */
  public static function getJsonDecode($context_data);

}
