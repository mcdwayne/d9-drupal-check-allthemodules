<?php

namespace Drupal\track_da_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generate export and clear form.
 */
class TrackDaFilesTableActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'table_action_form';
  }

 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  //if (isset($form_state['build_info']['args'][0]) && isset($form_state['build_info']['args'][1])) {
    // File report export.

  	$account = \Drupal::currentUser();
  	$admin_access = $account->hasPermission('administer track da files');
  	$init_access = $account->hasPermission('initialize tracked files displays datas');

    if ($admin_access) {
      $form['track_da_files_table_action']['track_da_files_export_files_datas'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Export datas into CSV file'),
        '#submit' => array('track_da_files_export_files_datas_submit'),
      );
    }
    // File report clear.
    if ($init_access) {
      $form['track_da_files_table_action']['track_da_files_clear_file_datas'] = array(
        '#type' => 'details',
        '#title' => $this->t('Clear file datas'),
        '#description' => $this->t('Datas will be removed for this file.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['track_da_files_table_action']['track_da_files_clear_file_datas']['clear'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Remove all datas for this file'),
        '#submit' => array('track_da_files_clear_file_datas_submit'),
      );
    }
  //}
  //else {
    // Main report export.
    if ($admin_access) {
      $form['track_da_files_table_action']['track_da_files_export_files_datas'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Export datas into CSV file'),
        '#submit' => array('track_da_files_export_files_datas_submit'),
      );
    }
    // Main report clear.
    if ($init_access) {
      $form['track_da_files_table_action']['track_da_files_clear_file_datas'] = array(
        '#type' => 'details',
        '#title' => $this->t('Clear all files datas'),
        '#description' => $this->t('Datas will be cleared for all files.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['track_da_files_table_action']['track_da_files_clear_file_datas']['clear'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Clear datas'),
        '#submit' => array('track_da_files_clear_file_datas_submit'),
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

  	return $this->track_da_files_clear_file_datas_submit();
  }


}


