<?php

namespace Drupal\setka_Editor\Validate;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form API callback. Validate setka editor value.
 */
class SetkaEditorValidate {

  /**
   * Validates given element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    if (!empty($element['#value'])) {
      $currentValue = $element['#value'];
      if ($decoded = Json::decode($currentValue)) {
        if ($decoded['postTheme'] && $decoded['postGrid'] && $decoded['postHtml'] && !empty($decoded['postUuid'])) {
          $setkaEditorUuid = &drupal_static('setkaEditorUuid');
          $setkaEditorUuid = $decoded['postUuid'];
        }
      }
    }
  }

}
