<?php

/**
 * @file
 * Admin page callback file for the timelogin module.
 */

/**
 * Form constructor to the set the timeslot add form.
 *
 * @see timelogin_timeslot_validate()
 * @see timelogin_timeslot_submit()
 *
 * @ingroup forms
 */
function timelogin_timeslot_form($form, &$form_state, $id = NULL, $operation = NULL) {
  if (is_numeric($id) && $operation == 'edit') {
    $tl_edit = db_select('time_login', 'tl')
        ->fields('tl')
        ->condition('id', $id)
        ->execute()->fetchAssoc();
    $form['id'] = array(
      '#type' => 'hidden',
      '#title' => t('Id:'),
      '#value' => isset($tl_edit['id']) ? $tl_edit['id'] : '',
    );
  }
  $roles = user_roles();
  unset($roles[1], $roles[3]);
  $form['timelogin_role_id'] = array(
    '#type' => 'select',
    '#title' => t('Select Role'),
    '#options' => $roles,
    '#default_value' => isset($tl_edit['timelogin_role_id']) ? $tl_edit['timelogin_role_id'] : '',
    '#description' => t('Select Role'),
    '#required' => TRUE,
  );
  $form['timelogin_from_time'] = array(
    '#type' => 'select',
    '#title' => 'From Time',
    '#default_value' => isset($tl_edit['timelogin_from_time']) ? $tl_edit['timelogin_from_time'] : '',
    '#options' => timelogin_timeslot_generator(0),
    '#description' => '<p>' . t('Select from time') . '</p>',
    '#required' => TRUE,
  );
  $form['timelogin_to_time'] = array(
    '#type' => 'select',
    '#title' => 'To Time',
    '#default_value' => isset($tl_edit['timelogin_to_time']) ? $tl_edit['timelogin_to_time'] : '',
    '#options' => timelogin_timeslot_generator(1),
    '#description' => '<p>' . t('Select to time') . '</p>',
    '#required' => TRUE,
  );
  $form['timelogin_save'] = array(
    '#type' => 'submit',
    '#name' => 'submit',
    '#value' => 'Save',
  );
  return $form;
}

/**
 * Form validation handler for timelogin_timeslot_form().
 *
 * @see timelogin_timeslot_form_submit()
 */
function timelogin_timeslot_form_validate($form, &$form_state) {
  $from_time = strtotime($form_state['values']['timelogin_from_time']);
  $to_time = strtotime($form_state['values']['timelogin_to_time']);
  if ($to_time <= $from_time) {
    form_set_error('timelogin_to_time', t('From time should be less than To time.'));
  }
}

/**
 * Form submission handler for timelogin_timeslot_form().
 *
 * @see timelogin_timeslot_form_validate()
 */
function timelogin_timeslot_form_submit($form, &$form_state) {
  global $user;
  $values = $form_state['values'];
  if (isset($values['id'])) {
    db_update('time_login')
      ->fields(array(
        'timelogin_role_id' => $values['timelogin_role_id'],
        'timelogin_from_time' => $values['timelogin_from_time'],
        'timelogin_to_time' => $values['timelogin_to_time'],
        'uid' => $user->uid,
        'updated' => REQUEST_TIME,
        'created' => REQUEST_TIME,
      ))
      ->condition('id', $values['id'])
      ->execute();
  }
  else {
    db_insert('time_login')
      ->fields(array(
        'timelogin_role_id' => $values['timelogin_role_id'],
        'timelogin_from_time' => $values['timelogin_from_time'],
        'timelogin_to_time' => $values['timelogin_to_time'],
        'uid' => $user->uid,
        'updated' => REQUEST_TIME,
        'created' => REQUEST_TIME,
      ))
      ->execute();
  }
  drupal_set_message(t('Your record has been saved successfully!'));
  drupal_goto('admin/timelogin');
}

/**
 * Page callback: Generates the time slot listing.
 *
 * @return string
 *   A renderable form array for the respective request.
 */
function timelogin_manage_timeslot() {
  $header = array(
    array('data' => t('ID'), 'field' => 'id', 'sort' => 'asc'),
    array('data' => t('Role'), 'field' => 'timelogin_role_id'),
    array('data' => t('From time'), 'field' => 'timelogin_from_time'),
    array('data' => t('To time'), 'field' => 'timelogin_to_time'),
    array('data' => t('Action')),
  );
  $rows = array();
  $query = db_select('time_login', 'tl')->fields('tl');
  $table_sort = $query->extend('TableSort')
    ->orderByHeader($header);
  $pager = $table_sort->extend('PagerDefault')
    ->limit(10);
  $results = $pager->execute();
  $roles = user_roles();
  foreach ($results as $row) {
    $rows[] = array($row->id,
      $roles[$row->timelogin_role_id],
      $row->timelogin_from_time,
      $row->timelogin_to_time,
      l(t('Edit'), 'admin/timelogin_timeslot/' . $row->id . '/edit') . ' | ' .
      l(t('Delete'), 'admin/timelogin_timeslot/' . $row->id . '/delete', array('query' => array('destination' => 'admin/timelogin'))),
    );
  }
  return theme('table', array(
    'header' => $header,
    'rows' => $rows,
  )) . theme('pager');
}

/**
 * Function to confirm delete action on timelogin timeslot.
 *
 * @see timelogin_timeslot_form_submit()
 */
function timelogin_timeslot_delete_confirm($form, &$form_state, $id) {
  $form['tlid'] = array(
    '#type' => 'value',
    '#value' => $id,
  );
  return confirm_form($form,
    t('Are you sure you want to delete this time slot?'), '',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}

/**
 * Form submission handler for timelogin_timeslot_delete_confirm().
 *
 * @see timelogin_timeslot_delete_confirm()
 */
function timelogin_timeslot_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    timelogin_timeslot_delete($form_state['values']['tlid']);
    drupal_set_message(t('Specific time slot has been deleted successfully.'));
  }
}

/**
 * Function to delete time slot.
 *
 * @param int $tlid
 *   Time slot id to be deleted.
 */
function timelogin_timeslot_delete($tlid) {
  db_delete('time_login')
    ->condition('id', $tlid)
    ->execute();
}

/**
 * Function to create a time slot in 15 mins interval.
 *
 * @param int $to_time_flag
 *   Contains 0 or 1 so as to return proper array of time intervals.
 *
 * @return array
 *   A array which contains 0 to 24 hours with 15 mins time interval.
 */
function timelogin_timeslot_generator($to_time_flag = 0) {
  $total_slots = array();
  $minutes = array('00', '15', '30', '45');
  for ($hours = 0; $hours < 24; $hours++) {
    foreach ($minutes as $value) {
      if ($hours < 10) {
        $total_slots['0' . $hours . ':' . $value] = '0' . $hours . ':' . $value;
      }
      else {
        $total_slots[$hours . ':' . $value] = $hours . ':' . $value;
      }
    }
  }
  if ($to_time_flag) {
    // Removing the first option and adding the last option.
    unset($total_slots['00:00']);
    $total_slots['24:00'] = '24:00';
  }
  return $total_slots;
}
