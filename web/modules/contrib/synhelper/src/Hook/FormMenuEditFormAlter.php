<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormMenuEditFormAlter - Main menu fixes.
 */
class FormMenuEditFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $message = t("Some options have been hidden using @class", ['@class' => __CLASS__]);
    drupal_set_message($message, 'warning');
    if ($form['id']['#default_value'] == 'main') {
      $form['id']['#prefix'] = '<div class="element-hidden">';
      $form['id']['#suffix'] = '</div>';
      $form['label']['#type'] = 'hidden';
      $form['label']['#disabled'] = TRUE;
      $form['description']['#type'] = 'hidden';
      $form['description']['#disabled'] = TRUE;
      $form['langcode']['#prefix'] = '<div class="element-hidden">';
      $form['langcode']['#disabled'] = TRUE;
      $form['langcode']['#suffix'] = '</div>';
    }
  }

}
