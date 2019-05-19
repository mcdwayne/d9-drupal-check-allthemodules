<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * FormFieldUiFieldStorageAddFormAlter - Field Form Warning.
 */
class FormFieldUiFieldStorageAddFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $path = \Drupal::request()->getRequestUri();
    $type = substr($path, 30, -17);
    $message = t("Use '@type' prefix for field machine-name! Example: field_[@type_position] for Position field.<br>
<small>@class</small>", ['@class' => __CLASS__, '@type' => $type]);
    drupal_set_message(Markup::create($message), 'warning');
  }

}
