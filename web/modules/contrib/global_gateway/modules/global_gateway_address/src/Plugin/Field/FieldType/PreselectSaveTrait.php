<?php

namespace Drupal\global_gateway_address\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait PreselectSaveTrait.
 *
 * @package Drupal\global_gateway_address\Plugin\Field\FieldType
 */
trait PreselectSaveTrait {

  /**
   * Custom submit handler for correctly save the custom field settings option.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function submitPreselectSetting(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue(['default_value_input', 'preselect_user_region_enabled']);
    \Drupal::routeMatch()
      ->getParameters()
      ->get('field_config')
      ->setSetting('preselect_user_region_enabled', $value)
      ->save();
  }

}
