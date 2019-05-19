<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormNodeFormAlter.
 */
class FormNodeFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $formkey = str_replace("_edit", "", $form_id);
    $type = substr($formkey, 5, -5);
    if ($type == 'page') {
      // Node-page form.
      $form['menu']['#weight'] = -10;
      $form['menu']['#group'] = FALSE;
      $form['menu']['#open'] = FALSE;
      $form['menu']['enabled']['#default_value'] = FALSE;
      $form['menu']['link']['description']['#type'] = 'hidden';
      $form['menu']['link']['weight']['#type'] = 'hidden';
      $form['menu']['link']['menu_parent']['#prefix'] = '<div class="element-hidden">';
      $form['menu']['link']['menu_parent']['#disabled'] = FALSE;
      $form['menu']['link']['menu_parent']['#suffix'] = '</div>';
    }

    // Meta-data.
    if (isset($form['meta'])) {
      $form['meta']['#access'] = FALSE;
    }
    // Revision Log - hide.
    if (isset($form['revision_log'])) {
      $form['revision_log']['#type'] = 'hidden';
      $form['revision_information']['#group'] = FALSE;
      $form['revision_information']['#open'] = FALSE;
    }
    // Path - collapse.
    if (isset($form['path_settings'])) {
      $form['path_settings']['#group'] = FALSE;
      $form['path_settings']['#open'] = FALSE;
    }
    // Attach - collapse.
    if (isset($form['field_attach'])) {
      $form['field_attach']['widget']['#open'] = FALSE;
    }
    // Gallery - collapse.
    if (isset($form['field_gallery'])) {
      $form['field_gallery']['widget']['#open'] = FALSE;
    }
  }

}
