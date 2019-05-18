<?php

namespace Drupal\fapi_validation;

use Drupal\Core\Form\FormStateInterface;

/**
 * FapiValidationService.
 *
 * Execute filters and validation at form elements.
 */
class FapiValidationService {

  /**
   * Process element validators and filters.
   *
   * Allows both #validators and #filters values. Run on form rendering. Only
   * adds filters and validators on form submission if the values have been
   * provided. Saves us from appending a check to every single item on
   * submission.
   */
  public static function process(array &$element, FormStateInterface &$form_state) {
    if ((isset($element['#filters']) || isset($element['#validators'])) && (!isset($element['#element_validate']) || !is_array($element['#element_validate']))) {
      $element['#element_validate'] = [];
    }

    if (isset($element['#filters']) && is_array($element['#filters'])) {
      // Check if element validate is already empty, and if so make variable for
      // merging in values an empty array and put at first place.
      array_unshift($element['#element_validate'], '\Drupal\fapi_validation\FapiValidationService::filter');
    }

    if (isset($element['#validators']) && is_array($element['#validators'])) {
      $element['#element_validate'][] = '\Drupal\fapi_validation\FapiValidationService::validate';
    }

    return $element;
  }

  /**
   * Perform FAPI Validation filters.
   *
   * @param array &$element
   *   Forme Element.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   Form State.
   */
  public static function filter(array &$element, FormStateInterface &$form_state) {
    $manager = \Drupal::service('plugin.manager.fapi_validation_filters');
    $manager->filter($element, $form_state);
  }

  /**
   * Perform FAPI Validation rules.
   *
   * @param array &$element
   *   Forme Element.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   Form State.
   */
  public static function validate(array &$element, FormStateInterface &$form_state) {
    $manager = \Drupal::service('plugin.manager.fapi_validation_validators');
    $manager->validate($element, $form_state);
  }

}
