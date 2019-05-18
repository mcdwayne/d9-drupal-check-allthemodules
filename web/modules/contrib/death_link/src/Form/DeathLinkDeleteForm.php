<?php

namespace Drupal\death_link\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Death Link entities.
 */
class DeathLinkDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->getEntity()->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.death_link.collection');
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
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->getEntity()->delete();

    drupal_set_message($this->t('content @type: deleted @label.', [
      '@type' => $this->getEntity()->bundle(),
      '@label' => $this->getEntity()->label(),
    ]));

    $formState->setRedirectUrl($this->getCancelUrl());
  }

}
