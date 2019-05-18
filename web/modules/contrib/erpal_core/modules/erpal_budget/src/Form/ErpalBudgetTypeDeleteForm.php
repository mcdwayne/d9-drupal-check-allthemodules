<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\Form\ErpalBudgetTypeDeleteForm.
 */

namespace Drupal\erpal_budget\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form that handles the removal of Erpal Budget type entities.
 */
class ErpalBudgetTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this Erpal Budget type: @name?', array('@name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.erpal_budget_type.list');
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
    // Delete and set message
    $this->entity->delete();
    drupal_set_message($this->t('The Erpal Budget type @label has been deleted.', array('@label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
