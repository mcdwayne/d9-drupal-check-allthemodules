<?php

/**
 * @file
 * Contains Drupal\domain_redirect\Form\DomainRedirectDeleteForm.
 */

namespace Drupal\domain_redirect\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DomainRedirectEditForm
 *
 * Provides the delete form for the domain redirect entity.
 *
 * @package Drupal\domain_redirect\Form
 *
 * @ingroup domain_redirect
 */
class DomainRedirectDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the redirect for domain %domain?', [
      '%domain' => $this->entity->getDomain(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete redirect');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.domain_redirect.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Domain redirect for domain %domain was deleted.', [
      '%domain' => $this->entity->getDomain(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
