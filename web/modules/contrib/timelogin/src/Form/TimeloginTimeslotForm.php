<?php

/**
 * @file
 * Contains \Drupal\timelogin\Form\TimeloginTimeslotForm.
 */

namespace Drupal\timelogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;

class TimeloginTimeslotForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'timelogin_timeslot_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $id = NULL, $operation = NULL) {
    if (is_numeric($id) && $operation == 'edit') {
      $tl_edit = db_select('time_login', 'tl')
          ->fields('tl')
          ->condition('id', $id)
          ->execute()->fetchAssoc();
      $form['id'] = [
        '#type' => 'hidden',
        '#title' => t('Id:'),
        '#value' => isset($tl_edit['id']) ? $tl_edit['id'] : '',
      ];
    }
    //Get all roles
    $rolesArray = \Drupal\user\Entity\Role::loadMultiple();
    $roles = array_keys($rolesArray);
    unset($roles[0], $roles[2]);
    $form['timelogin_role_id'] = [
      '#type' => 'select',
      '#title' => t('Select Role'),
      '#options' => $roles,
      '#default_value' => isset($tl_edit['timelogin_role_id']) ? $tl_edit['timelogin_role_id'] : '',
      '#description' => t('Select Role'),
      '#required' => TRUE,
    ];
    $form['timelogin_from_time'] = [
      '#type' => 'select',
      '#title' => 'From Time',
      '#default_value' => isset($tl_edit['timelogin_from_time']) ? $tl_edit['timelogin_from_time'] : '',
      '#options' => timelogin_timeslot_generator(0),
      '#description' => '<p>' . t('Select from time') . '</p>',
      '#required' => TRUE,
    ];
    $form['timelogin_to_time'] = [
      '#type' => 'select',
      '#title' => 'To Time',
      '#default_value' => isset($tl_edit['timelogin_to_time']) ? $tl_edit['timelogin_to_time'] : '',
      '#options' => timelogin_timeslot_generator(1),
      '#description' => '<p>' . t('Select to time') . '</p>',
      '#required' => TRUE,
    ];
    $form['timelogin_save'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }
  
  /**
   * 
   * @param array $form
   * @param FormStateInterface $form_state.
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $from_time = strtotime($form_state->getValue(['timelogin_from_time']));
    $to_time = strtotime($form_state->getValue(['timelogin_to_time']));
    if ($to_time <= $from_time) {
      $form_state->setErrorByName('timelogin_to_time', t('From time should be less than To time.'));
    }
  }
  
  /**
   * 
   * @param array $form
   * @param FormStateInterface $form_state.
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $userCurrent = \Drupal::currentUser();
    $values = $form_state->getValues();
    if (isset($values['id'])) {
      db_update('time_login')
        ->fields([
          'timelogin_role_id' => $values['timelogin_role_id'],
          'timelogin_from_time' => $values['timelogin_from_time'],
          'timelogin_to_time' => $values['timelogin_to_time'],
          'uid' => $userCurrent->id(),
          'updated' => REQUEST_TIME,
          'created' => REQUEST_TIME,
        ])
        ->condition('id', $values['id'])
        ->execute();
    }
    else {
      db_insert('time_login')
        ->fields([
          'timelogin_role_id' => $values['timelogin_role_id'],
          'timelogin_from_time' => $values['timelogin_from_time'],
          'timelogin_to_time' => $values['timelogin_to_time'],
          'uid' => $userCurrent->id(),
          'updated' => REQUEST_TIME,
          'created' => REQUEST_TIME,
        ])
        ->execute();
    }
    drupal_set_message(t('Your record has been saved successfully!'));
    $form_state->setRedirectUrl(new Url('timelogin.manage_timeslot'));
  }

}
