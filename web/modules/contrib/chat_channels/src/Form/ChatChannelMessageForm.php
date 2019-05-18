<?php

namespace Drupal\chat_channels\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Chat channel message edit forms.
 *
 * @ingroup chat_channels
 */
class ChatChannelMessageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\chat_channels\Entity\ChatChannelMessage */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Chat channel message.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Chat channel message.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.chat_channel_message.canonical', ['chat_channel_message' => $entity->id()]);
  }

}
