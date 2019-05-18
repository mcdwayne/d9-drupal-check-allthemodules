<?php


namespace Drupal\chatroom\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for chatroom forms.
 */
class ChatroomForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    if ($this->entity->id()) {
      drupal_set_message($this->t('The chatroom %chatroom has been updated.', ['%chatroom' => $this->entity->label()]));
      $form_state->setRedirectUrl($this->entity->urlInfo('canonical'));
    }
    else {
      drupal_set_message($this->t('The chatroom %chatroom has been added.', ['%chatroom' => $this->entity->label()]));
    }
  }
}
