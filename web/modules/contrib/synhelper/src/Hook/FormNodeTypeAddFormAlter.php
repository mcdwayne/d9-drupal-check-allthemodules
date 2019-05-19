<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormNodeTypeAddFormAlter.
 */
class FormNodeTypeAddFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $form['submission']['title_label']['#default_value'] = '';
    $form['submission']['preview_mode']['#default_value'] = 0;
    $form['workflow']['options']['#default_value'] = ['status', 'revision'];
    $form['display']['display_submitted']['#default_value'] = FALSE;
    $form['menu']['menu_options']['#default_value'] = [];
  }

}
