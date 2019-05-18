<?php

namespace Drupal\erf\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ParticipantTypeForm.
 */
class ParticipantTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $participant_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $participant_type->label(),
      '#description' => $this->t("Label for the Participant type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $participant_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\erf\Entity\ParticipantType::load',
      ],
      '#disabled' => !$participant_type->isNew(),
    ];

    $form['reference_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create or reference user account on save'),
      '#description' => $this->t('When a participant of this type is saved, a Drupal user account with the same email address will be linked to it. New user accounts will be created if necessary.'),
      '#default_value' => $participant_type->get('reference_user'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $participant_type = $this->entity;
    $status = $participant_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Participant type.', [
          '%label' => $participant_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Participant type.', [
          '%label' => $participant_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($participant_type->toUrl('collection'));
  }

}
