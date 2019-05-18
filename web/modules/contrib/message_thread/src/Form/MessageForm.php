<?php

namespace Drupal\message_thread\Form;

use Drupal\message_ui\Form\MessageForm as MessageMessageForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the message_thread message entity edit forms.
 *
 * @ingroup message_thread
 */
class MessageForm extends MessageMessageForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\message\Entity\Message $message */
    $message = $this->entity;

    $template = \Drupal::entityTypeManager()->getStorage('message_template')->load($this->entity->bundle());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
