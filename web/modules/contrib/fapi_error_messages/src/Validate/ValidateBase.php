<?php

namespace Drupal\fapi_error_messages\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class ValidateBase {

  /**
   * Process an error message.
   */
  public static function processErrorMessage(&$element, string $error_key, TranslatableMarkup $error_message, FormStateInterface $form_state) {
    if (!empty($element['#attributes']['data-validation-' . $error_key . '-message'])) {
      $error_message = $element['#attributes']['data-validation-' . $error_key . '-message'];
    }

    $form_state->setError($element, $error_message);
  }

}