<?php

namespace Drupal\chatbot\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MessageTypeForm.
 *
 * @package Drupal\chatbot\Form
 */
class MessageTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $chatbot_message_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $chatbot_message_type->label(),
      '#description' => $this->t("Label for the Message type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $chatbot_message_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\chatbot\Entity\MessageType::load',
      ],
      '#disabled' => !$chatbot_message_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $chatbot_message_type = $this->entity;
    $status = $chatbot_message_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Message type.', [
          '%label' => $chatbot_message_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Message type.', [
          '%label' => $chatbot_message_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($chatbot_message_type->toUrl('collection'));
  }

}
