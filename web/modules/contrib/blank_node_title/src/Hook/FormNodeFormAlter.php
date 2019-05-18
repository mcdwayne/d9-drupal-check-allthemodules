<?php

namespace Drupal\blank_node_title\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormNodeFormAlter.
 */
class FormNodeFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $enable = FALSE;
    $config = \Drupal::config('blank_node_title.settings');
    $fid = str_replace("_edit", "", $form_id);
    $formkey = substr($fid, 5, -5);
    if ($config->get('node-mode') == 'all') {
      $enable = TRUE;
    }
    elseif ($config->get('node-mode') == 'custom') {
      $bundles = $config->get('node-bundles');
      if (isset($bundles[$formkey]) && $bundles[$formkey]) {
        $enable = TRUE;
      }
    }
    if ($enable) {
      if (isset($form['title']['widget'][0]['value']['#required'])) {
        $form['title']['widget']['#required'] = FALSE;
        $form['title']['widget'][0]['#required'] = FALSE;
        $form['title']['widget'][0]['value']['#required'] = FALSE;
        $tm = ['@path' => "/admin/structure/types/manage/$formkey/form-display"];
        $info = t('Use `-` to autofill, or remove from form `@path`', $tm);
        $form['title']['widget'][0]['value']['#description'] = $info;
      }
    }
  }

}
