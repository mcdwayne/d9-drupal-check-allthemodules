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
   *
   * Updates the message object by processing the submitted values.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the node object from the submitted values.
    parent::submitForm($form, $form_state);

    /* @var $message Message */
    $message = $this->entity;
    $values = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Redirect to message view display if user has access.
    if ($message->access('view')) {
      $form_state->setRedirect('entity.message.canonical', ['message' => $message->id()]);
    }
    else {
      $form_state->setRedirect('<front>');
    }
    // @todo : for node they clear temp store here, but perhaps unused with
    // message.
    // In the unlikely case something went wrong on save, the message will be
    // rebuilt and message form redisplayed.
    drupal_set_message(t('The message could not be saved.'), 'error');
    $form_state->setRebuild();
  }

}
