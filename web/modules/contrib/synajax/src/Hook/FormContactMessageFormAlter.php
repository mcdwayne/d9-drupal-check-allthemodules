<?php

namespace Drupal\synajax\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * AjaxContactForm.
 */
class FormContactMessageFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, FormStateInterface $form_state, $form_id) {
    // Only if ajax-submit.
    if (isset($form['actions']['submit']['#ajax'])) {
      $ajax = FALSE;
      $config = \Drupal::config('synajax.settings');
      if ($config->get('contact_message-mode') == 'all') {
        $ajax = TRUE;
      }
      elseif ($config->get('contact_message-mode') == 'custom') {
        $bundles = $config->get('contact_message-bundles');
        $formkey = substr($form_id, 16, -5);
        if (isset($bundles[$formkey]) && $bundles[$formkey]) {
          $ajax = TRUE;
        }
      }
      if ($ajax) {
        $form['#validate'][] = 'Drupal\synajax\Hook\FormContactMessageFormAlter::formValidate';
      }
    }

  }

  /**
   * Validate form submit.
   */
  public static function formValidate(array &$form, FormStateInterface $form_state) {
    // Make sure it's ajax.
    if (isset($_POST['_drupal_ajax']) && $_POST['_drupal_ajax'] == 1) {
    }
    else {
      $form_state->setErrorByName('', t("Submit error, reload page and try again"));
    }
  }

}
