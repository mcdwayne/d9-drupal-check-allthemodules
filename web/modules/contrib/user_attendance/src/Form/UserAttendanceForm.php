<?php

namespace Drupal\user_attendance\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for User attendance edit forms.
 *
 * @ingroup user_attendance
 */
class UserAttendanceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\user_attendance\Entity\UserAttendance */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User attendance.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User attendance.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.user_attendance.canonical', ['user_attendance' => $entity->id()]);
  }

}
