<?php

namespace Drupal\Formdefaults\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminForm extends FormBase {
  public function getFormId() {
    return 'formdefaults_admin_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['instructions'] = array(
      '#type' => 'markup',
      '#markup' => '<p>To alter the default labels and text descriptions associated with a form, enable the form ' .
                   'defaults editor below. Navigate to the form you wish to modify and click the [edit] link on ' .
                   'the field you want to edit. You\'ll be allowed to edit field titles and markup fields ' .
                   '(like this one), as well as the textual descriptions for each field. It\'s also possible to ' .
                   'add form elements and hide those already present.</p>' .
                   '<p>Use the controls above to manage those forms you\'ve modified.</p>',
    );

    if (@$_SESSION['formdefaults_enabled']) {
      $form['disable'] = array(
        '#type' => 'submit',
        '#value' => 'disable',
      );
    }
    else {
      $form['enable'] = array(
        '#type' => 'submit',
        '#value' => 'enable',
      );
    }
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Toggle the form editor controls on and off
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editor_enabled = @$_SESSION['formdefaults_enabled'];
    if ($editor_enabled) {
      $_SESSION['formdefaults_enabled']=FALSE;
      drupal_set_message(t('Form defaults editor is now disabled'));
    }
    else {
      $_SESSION['formdefaults_enabled']=TRUE;
      drupal_set_message(t('Form defaults editor is now enabled'));
    }
  }
}
