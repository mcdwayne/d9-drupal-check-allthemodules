<?php

namespace Drupal\commerce_xero\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides interface to delete strategy entities.
 */
class StrategyDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the strategy, %name', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_xero_strategy.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    \drupal_set_message($this->t('Successfully deleted strategy %name.', ['%name' => $this->entity->label()]));

    $form_state->setRedirect($this->getCancelUrl());
  }

}
