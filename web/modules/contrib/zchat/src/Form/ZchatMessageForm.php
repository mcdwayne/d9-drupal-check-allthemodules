<?php

namespace Drupal\zchat\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Zchat Message edit forms.
 *
 * @ingroup zchat
 */
class ZchatMessageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\zchat\Entity\ZchatMessage */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $form['message_text'] = [
      '#title' => $this->t('Message text'),
      '#type' => 'textfield',
      '#default_value' => $entity->getMessageText(),
    ];

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
        drupal_set_message($this->t('Created the %label Zchat Message.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Zchat Message.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.zchatmessage.canonical', ['zchatmessage' => $entity->id()]);
  }

}
