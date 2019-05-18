<?php

/**
 * @file
 * Contains Drupal\hawk_auth\Form\HawkDeleteCredentialForm.
 */

namespace Drupal\hawk_auth\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form for deleting a hawk credential.
 */
class HawkDeleteCredentialForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hawk_delete_credential_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone. It will disable all clients
      relying on this credential.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->url('hawk_auth.user_credential', ['user' => $this->currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\hawk_auth\Entity\HawkCredentialInterface $credential */
    $credential = $this->getEntity();

    if (empty($credential) || $credential->getOwnerId() != $this->currentUser()->id()) {
      throw new AccessDeniedHttpException('Invalid credential owner', NULL, 403);
    }

    $credential->delete();

    $form_state->setRedirect('hawk_auth.user_credential', ['user' => $credential->getOwnerId()]);
  }

}
