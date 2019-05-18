<?php

namespace Drupal\pardot\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PardotScoreDeleteForm.
 *
 * Provides a confirm form for deleting the Pardot Score.
 *
 * @package Drupal\pardot\Form
 *
 * @ingroup pardot
 */
class PardotScoreDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete Pardot Score %label?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete Pardot Score %label? This action cannot be undone.', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Pardot Score');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('pardot.scores.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Pardot Score %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
