<?php

namespace Drupal\webform_epetition\Validate;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form API callback. Validate element value.
 */
class WebformEpetitionValidate {
  /**
   * Validates given element.
   *
   * @param array              $element      The form element to process.
   * @param FormStateInterface $formState    The form state.
   * @param array              $form The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $webformKey = $element['#webform_key'];
    $value = $formState->getValue($webformKey);

    // TODO: add postcode validation.
    if (empty($value['ep_postcode'])) {
      $error = TRUE;
    }

    if (!empty($value['ep_email_to'])) {
      $emails = explode(',', $value['ep_email_to']);
      foreach($emails as $email_address) {
        if(filter_var($email_address, FILTER_VALIDATE_EMAIL))
        {
          $emails_error = FALSE;
        }
        else {
          $emails_error = TRUE;
        }
      }
    }
    else {
      $error = TRUE;
    }

    /** @var $error */
    if ($error) {
      $formState->setError(
        $element,
        t('Please enter a valid postcode and click on "Find Representative".')
      );
    }

    /** @var $emails_error */
    if ($emails_error) {
      if (isset($value['ep_postcode'])) {
        $tArgs = array(
          '%value' => $value['ep_postcode'],
        );
        $formState->setError(
          $element,
          t('The postcode %value has not come up with a match. Please enter another and click on "Find Representative".', $tArgs)
        );
      } else {
        $formState->setError($element);
      }
    }
  }
}