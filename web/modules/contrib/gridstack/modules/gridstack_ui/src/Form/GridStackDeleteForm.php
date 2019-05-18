<?php

namespace Drupal\gridstack_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a GridStack optionset.
 */
class GridStackDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the GridStack optionset %label?', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.gridstack.collection');
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

    drupal_set_message($this->t('Optionset %label was deleted', ['%label' => $this->entity->label()]));
    $this->logger('user')->notice('Deleted optionset %oid (%label)', ['%oid' => $this->entity->id(), '%label' => $this->entity->label()]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
