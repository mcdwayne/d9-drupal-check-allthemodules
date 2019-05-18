<?php

namespace Drupal\chat_channels\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ChatChannelTypeForm.
 *
 * @package Drupal\chat_channels\Form
 */
class ChatChannelTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $chat_channel_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $chat_channel_type->label(),
      '#description' => $this->t("Label for the Chat channel type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $chat_channel_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\chat_channels\Entity\ChatChannelType::load',
      ],
      '#disabled' => !$chat_channel_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $chat_channel_type = $this->entity;
    $status = $chat_channel_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Chat channel type.', [
          '%label' => $chat_channel_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Chat channel type.', [
          '%label' => $chat_channel_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($chat_channel_type->urlInfo('collection'));
  }

}
