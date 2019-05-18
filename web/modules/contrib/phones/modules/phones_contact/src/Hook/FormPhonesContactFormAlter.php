<?php

namespace Drupal\phones_contact\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Hook Cron.
 */
class FormPhonesContactFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, FormStateInterface &$form_state, $form_id) {
    $phone = \Drupal::request()->query->get('phone');
    if ($phone) {
      $form['field_hphone']['widget'][0]['value']['#default_value'] = $phone;
    }
    $form['revision_log_message']['#prefix'] = '<div class="element-hidden">';
    $form['revision_log_message']['widget'][0]['#disabled'] = TRUE;
    $form['revision_log_message']['#suffix'] = '</div>';

  }

}
