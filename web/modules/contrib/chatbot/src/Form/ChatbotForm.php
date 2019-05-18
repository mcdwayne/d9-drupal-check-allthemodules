<?php

namespace Drupal\chatbot\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Chatbot edit forms.
 *
 * @ingroup chatbot
 */
class ChatbotForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\chatbot\Entity\Chatbot */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Chatbot.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Chatbot.', [
          '%label' => $entity->label(),
        ]));
    }

   // Rebuild routing on saving the chatbot settings.
   \Drupal::service('router.builder')->rebuild();

    $form_state->setRedirect('entity.chatbot.canonical', ['chatbot' => $entity->id()]);
  }

}
