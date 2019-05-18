<?php

namespace Drupal\server_notice\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a server notice delete form.
 */
class ServerNoticeDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the server notice for %fqdn?', ['%fqdn' => $this->entity->getFqdn()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('server_notice.list');
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
    drupal_set_message(t('The server notice for %fqdn has been deleted.', ['%fqdn' => $this->entity->getFqdn()]));
    $form_state->setRedirect('server_notice.list');
  }

}
