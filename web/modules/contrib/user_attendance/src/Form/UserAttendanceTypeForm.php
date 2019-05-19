<?php

namespace Drupal\user_attendance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserAttendanceTypeForm.
 *
 * @package Drupal\user_attendance\Form
 */
class UserAttendanceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_attendance_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_attendance_type->label(),
      '#description' => $this->t("Label for the User attendance type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_attendance_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_attendance\Entity\UserAttendanceType::load',
      ],
      '#disabled' => !$user_attendance_type->isNew(),
    ];

    $attendance_period_limit = $user_attendance_type->get('attendance_period_type');
    $form['attendance_period_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Period Type'),
      '#description' => $this->t("Over wat period do we want to see the attendance records being created and fulfilled."),
      '#default_value' => isset($attendance_period_limit) ? $attendance_period_limit : 'till_end',
      '#options' => [
        'till_end' => $this->t('Till attendance end'),
        'by_day' => $this->t('Register by day'),
      ],
      '#required' => TRUE,
    ];

    $duplicate_protection = $user_attendance_type->get('duplicate_protection');
    $form['duplicate_protection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duplicate protection'),
      '#description' => $this->t("Number of seconds that should be between actions of start and end times before invalidating the record or new record. This is to prevent duplicate records when a user makes a small mistake"),
      '#default_value' => isset($duplicate_protection) ? $duplicate_protection : 0,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_attendance_type = $this->entity;
    $status = $user_attendance_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User attendance type.', [
          '%label' => $user_attendance_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User attendance type.', [
          '%label' => $user_attendance_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($user_attendance_type->toUrl('collection'));
  }

}
