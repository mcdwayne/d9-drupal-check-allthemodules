<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\admin_notes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * My Custom form.
 */
class AdminNotesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_notes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $note = NULL) {
    $account = $this->currentUser();
    $form['admin_note'] = array(
      '#type' => 'textarea',
      '#title' => t('Block contents'),
      '#description' => t('This text will appear in the example block.'),
      '#default_value' => $note,
      '#access' => $account->hasPermission('access admin notes'),
    );
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#access' => $account->hasPermission('access admin notes'),
      '#attributes' => array(
        'class' => array('delete-button'),
        'style' => 'display: ' . ($note ? ' block' : ' none'),
      ),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => ($note ? $this->t('Update') : $this->t('Save')),
      '#access' => $account->hasPermission('access admin notes'),
      '#attributes' => array(
        'class' => array('save-button', ($note ? 'delete-show' : '')),
      ),
    );

    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $button_value = $form_state->getValue('op');
    if ($button_value == 'Save' && $form_state->getValue('admin_note') == '') {
      $form_state->setErrorByName('admin_note', t('This is a required field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $button_value = $form_state->getValue('op');
    $current_path = \Drupal::service('path.current')->getPath();
    switch ($button_value) {
      case 'Delete':
        $message = $this->adminNotesRecordHandler($current_path, 'delete');
        break;

      case 'Save':
      case 'Update':
        $message = $this->adminNotesRecordHandler($current_path, 'save', $form_state->getValue('admin_note'));
        break;
    }
    drupal_set_message($message);
  }

  /**
   *
   */
  protected function adminNotesRecordHandler($path, $op, $note = '') {
    switch ($op) {
      case 'delete':
        db_delete('admin_notes')
            ->condition('path', $path)
            ->execute();

        $message = t('Your admin note has been deleted.');
      case 'save':
        $user = \Drupal::currentUser();
        $is_front_page = \Drupal::service('path.matcher')->isFrontPage();
        $path = \Drupal::service('path.current')->getPath();
        // Was there already a note added for this path.
        $path = $is_front_page ? '/' : $path ;
        if ($this->adminNotesNoteExists($path)) {
          db_update('admin_notes')
              ->fields(array(
                'uid' => $user->id(),
                'note' => $note,
                'timestamp' => time(),
              ))
              ->condition('path', $path)
              ->execute();

          $message = t('Your admin note has been updated.');
        }
        else {
          db_insert('admin_notes')
              ->fields(array(
                'uid' => $user->id(),
                'note' => $note,
                'path' => $path,
                'timestamp' => time(),
              ))
              ->execute();

          $message = t('Your admin note has been saved.');
        }

        return $message;
    }
  }

  /**
   *
   */
  protected function adminNotesNoteExists($path = '') {
    $front = Url::fromRoute('<front>')->toString();
    $front_page = \Drupal::config('system.site')->get('page.front');
    $page = \Drupal::service('path.current')->getPath();
    if ($front_page == $page) {
      $path = '/';
    }
    elseif ($path == '') {
      $path = \Drupal::service('path.current')->getPath();
    }

    $result = db_select('admin_notes', 'an')
        ->fields('an')
        ->condition('path', $path)
        ->execute()
        ->fetchAssoc();

    if (is_array($result)) {
      return $result;
    }
    else {
      return FALSE;
    }
  }

}
