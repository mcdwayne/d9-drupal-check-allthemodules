<?php

namespace Drupal\mason_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a Mason optionset.
 */
class MasonDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Mason optionset %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.mason.collection');
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

    drupal_set_message($this->t('Optionset %label was deleted', array('%label' => $this->entity->label())));
    $this->logger('user')->notice('Deleted optionset %oid (%label)', array('%oid' => $this->entity->id(), '%label' => $this->entity->label()));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
