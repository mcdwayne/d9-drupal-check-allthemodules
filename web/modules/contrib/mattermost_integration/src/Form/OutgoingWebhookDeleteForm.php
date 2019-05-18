<?php

namespace Drupal\mattermost_integration\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirmation for deleting an Outgoing Webhook.
 *
 * @package Drupal\mattermost_integration\Form
 */
class OutgoingWebhookDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mattermost_integration.outgoing_webhooks');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('Outgoing Webhook %name deleted', ['%name' => $this->entity->label()])
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
