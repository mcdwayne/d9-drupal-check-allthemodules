<?php

namespace Drupal\abstractpermissions\FormAlter;

class PermissionsFormMarkGoverned extends PermissionsFormAlterBase {

  public static function alterForm(array &$form) {
    parent::alterForm($form);
    $form['#attached']['library'][] = 'abstractpermissions/mark-governed';
  }

}
