<?php

namespace Drupal\abstractpermissions\Form;

use Drupal\abstractpermissions\FormAlter\PermissionsFormOnlyGoverned;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPermissionsForm;

class AbstractPermissionsCheckForm extends UserPermissionsForm {
  
  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'abstractpermissions_check';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['#access'] = FALSE;
    $form['#disabled'] = TRUE;
    PermissionsFormOnlyGoverned::alterForm($form['permissions']);
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

}
