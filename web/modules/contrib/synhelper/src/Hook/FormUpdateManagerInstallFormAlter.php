<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FormUpdateManagerInstallFormAlter.
 */
class FormUpdateManagerInstallFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    unset($form['project_url']);
    unset($form['information']);
    unset($form['project_upload']);
    $form['#prefix'] = t('<h3>Access denied by syndev</h3><p>Use composer require drupal/MODULENAME in drupal root folder.');
  }

}
