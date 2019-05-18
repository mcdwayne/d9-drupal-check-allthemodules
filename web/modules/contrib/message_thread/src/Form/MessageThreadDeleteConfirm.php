<?php

namespace Drupal\message_thread\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for message thread deletion.
 */
class MessageThreadDeleteConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the message thread %template?', ['%template' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the message list.
   */
  public function getCancelUrl() {
    return new Url('entity.user.canonical', ['user' => $this->entity->get('uid')->getValue()[0]['target_id']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('message_thread')->notice('@type: deleted message thread.', ['@type' => $this->entity->bundle()]);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
