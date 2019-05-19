<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\Form\WisskiPathbuilderDeleteForm.
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that handles the removal of flower entities
 */
class WisskiPathbuilderDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this pathbuilder: @id?',
    array('@id' => $this->entity->id()));
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    drupal_set_message(htmlentities(new Url('entity.wisski_pathbuilder.collection')));
    return new Url('entity.wisski_pathbuilder.collection');
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
    drupal_set_message($this->t('The pathbuilder @id has been deleted.',
    array('@id' => $this->entity->id())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}