<?php

/**
 * @file
 * Contains Drupal\delete_users_csv\DeleteUsersCsv
 */

namespace Drupal\delete_users_csv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DeleteUsersCsv extends FormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'delete_users_csv_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $validators = array(
      'file_validate_extensions' => array('csv'),
    );
    $form['users_file'] = [
      '#type' => 'managed_file',
      '#name' => 'my_file',
      '#title' => t('File *'),
      '#size' => 20,
      '#description' => t('CSV format only'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Upload CSV & Delete Users'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('users_file') == NULL) {
      $form_state->setErrorByName('users_file', 'Please upload a file in order to continue.');
    }
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users_emails = [];
    $file = $form_state->getValue('users_file');
    $fid = $file[0];
    $file = \Drupal\file\Entity\File::load($fid);
    $destination = $file->toArray()['uri'][0]['value'];
    $file = fopen($destination, "r");
    while (!feof($file)) {
      $row = fgetcsv($file);
      foreach ($row as $key => $value) {
        if (strpos($value, '@')) {
          $users_emails[] = $value;
        }
      }
    }
    fclose($file);
    $batch = array(
      'title' => t('Deleting Users ...'),
      'operations' => array(
        array(
          '\Drupal\delete_users_csv\DeleteUsersBatch::deleteUsers',
          array($users_emails)
        ),
      ),
      'finished' => '\Drupal\delete_users_csv\DeleteUsersBatch::deleteUsersCallback',
    );
    batch_set($batch);
  }
}
