<?php

namespace Drupal\evergreen\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an evergreen configuration.
 */
class EvergreenConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the configuration for %entity.%bundle?', array('%entity' => $this->entity->getEvergreenEntityType(), '%bundle' => $this->entity->getEvergreenBundle()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.evergreen_config.collection');
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
    drupal_set_message($this->t('Evergreen configuration has been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
