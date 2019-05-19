<?php

namespace Drupal\track_da_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generate export and clear form.
 */
class TrackDaFilesUserTableActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'track_da_files_user_table_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  	$account = \Drupal::currentUser();
  	$admin_access = $account->hasPermission('administer track da files');
  	$init_access = $account->hasPermission('initialize tracked files displays datas');

  	// User report export.
    if ($admin_access) {
      $form['track_da_files_user_table_action']['track_da_files_export_users_datas'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Export datas into CSV file'),
        '#submit' => array('track_da_files_export_users_datas_submit'),
      );
    }

    // User report clear.
    if ($init_access) {
      $form['track_da_files_user_table_action']['track_da_files_clear_user_datas'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Clear user datas'),
        '#description' => $this->t('Datas will be removed for this user.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['track_da_files_user_table_action']['track_da_files_clear_user_datas']['clear'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Remove all datas for this user'),
        '#submit' => array('track_da_files_clear_user_datas_submit'),
      );
    }
  //}
    return $form;
    //return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  	// Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  	// Handle submitted form data.
  }

}
