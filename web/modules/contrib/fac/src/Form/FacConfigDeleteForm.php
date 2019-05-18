<?php

namespace Drupal\fac\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FacConfigDeleteForm.
 *
 * @package Drupal\fac\Form
 */
class FacConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', [
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.fac_config.collection');
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
    $this->messenger()->addStatus($this->t('Fast Autocomplete configuration %label has been deleted.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
