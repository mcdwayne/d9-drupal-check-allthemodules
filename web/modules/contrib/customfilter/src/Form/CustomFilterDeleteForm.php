<?php

namespace Drupal\customfilter\Form;

// Base class for form that delete a configuration entity.
use Drupal\Core\Entity\EntityConfirmFormBase;

// Use base class for Url.
use Drupal\Core\Url;

// Load the Drupal interface for the current state of a form.
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a Custom Filter.
 */
class CustomFilterDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.customfilter.list');
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
    drupal_set_message($this->t('Filter %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state->setRedirect('entity.customfilter.list');
  }

}
