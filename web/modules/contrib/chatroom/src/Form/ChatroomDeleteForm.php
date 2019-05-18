<?php


namespace Drupal\chatroom\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a chatroom.
 */
class ChatroomDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.chatroom.edit_form', ['chatroom' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The chatroom %label has been deleted.', [
      '%label' => $this->entity->label(),
    ]);
  }

}
