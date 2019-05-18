<?php

namespace Drupal\chat_channels\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Chat channel member edit forms.
 *
 * @ingroup chat_channels
 */
class ChatChannelMemberForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\chat_channels\Entity\ChatChannelMember */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelMemberInterface $entity */
    $entity = $this->entity;
    parent::save($form, $form_state);

    $form_state->setRedirect('entity.chat_channel_member.canonical', ['chat_channel_member' => $entity->id()]);
  }

}
