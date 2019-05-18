<?php
namespace Drupal\jasm\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a JASM service.
 */
class JasmDeleteForm extends EntityConfirmFormBase {
  
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
    return new Url('entity.jasm.collection');
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
    drupal_set_message($this->t('JASM service %label has been deleted.', array('%label' => $this->entity->label())));
    
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}