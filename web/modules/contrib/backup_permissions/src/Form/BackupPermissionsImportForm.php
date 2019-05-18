<?php

namespace Drupal\backup_permissions\Form;

use Drupal\backup_permissions\BackupPermissionsStorageTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Form to restore permissions from CSV.
 */
class BackupPermissionsImportForm extends FormBase {

  use BackupPermissionsStorageTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backup_permissions_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $backup_data = NULL) {
    $url = Url::fromRoute('backup_permissions.settings');

    if (!empty($form_state->getValue('backup_data')) || $backup_data) {
      $roles = array();
      if ($backup_data) {
        $data = unserialize($backup_data);
        $form_state->setValue('backup_data', $backup_data);
      }
      else {
        $data = unserialize($form_state->getValue('backup_data'));
      }
      foreach ($data['roles'] as $name) {
        $roles[$name] = $name;
      }

      $permission_status = array(
        0 => $this->t('Restore all permissions.'),
        1 => $this->t('Restore enabled permissions only.'),
        2 => $this->t('Restore disabled permissions only.'),
      );
      $form['status'] = array(
        '#type' => 'radios',
        '#options' => $permission_status,
        '#title' => $this->t('Choose what to restore'),
        '#required' => TRUE,
        '#description' => $this->t('You can choose to selectively restore permissions for the module. Please choose the appropriate set of permissions to restore.'),
      );
      $form['backup_data'] = array(
        '#type' => 'hidden',
        '#value' => $form_state->getValue('backup_data'),
      );
      $form['roles'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#title' => $this->t('Roles To Restore'),
        '#required' => TRUE,
        '#description' => $this->t('Select roles permissions will be overridden and restored.'),
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Restore'),
        '#submit' => array('::selectRolesToRestore'),
      );

      $form['cancel'] = array(
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => $url,
        '#attributes' => ['class' => ['button']],
      );
    }
    else {
      $form['import'] = array(
        '#title' => $this->t('Import'),
        '#type' => 'managed_file',
        '#description' => $this->t('Allowed extensions: CSV'),
        '#upload_location' => 'public://tmp/',
        '#upload_validators' => array(
          'file_validate_extensions' => array('csv'),
        ),
        '#required' => TRUE,
      );
      $form['backup_data'] = array(
        '#type' => 'hidden',
        '#value' => '',
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Import Permissions'),
      );
      $form['back'] = array(
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => $url,
        '#attributes' => ['class' => ['button']],
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('backup_data'))) {
      $file_id = $form_state->getValue('import');

      $file = File::load($file_id['0']);
      $uri = $file->getFileUri();
      $handle = fopen($uri, 'r');
      $rows = array();
      $row = fgetcsv($handle);
      $columns = array();
      foreach ($row as $i => $header) {
        $columns[$i] = trim($header);
      }

      while ($row = fgetcsv($handle)) {
        $record = array();
        foreach ($row as $i => $field) {
          // This is pretty brittle... if someone screws up the field
          // names the data won't be written.
          $record[$columns[$i]] = $field;
        }
        $rows[] = $record;
      }
      fclose($handle);
      array_shift($columns);
      $data = array();
      $data['roles'] = $columns;
      $data['permissions'] = $rows;

      $form_state->setValue('backup_data', serialize($data));
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for restoring roles.
   */
  public function selectRolesToRestore(array &$form, FormStateInterface $form_state) {
    $roles = array();
    $status = $form_state->getValue('status');
    $backup_data = unserialize($form_state->getValue('backup_data'));

    $rows = $backup_data['permissions'];
    foreach ($form_state->getValue('roles') as $name) {
      if ($name) {
        $roles[] = $name;
      }
    }
    $this->resetRoles($roles, $rows, $status);
    $form_state->setRedirect('backup_permissions.settings');
  }

}
