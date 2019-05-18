<?php

/**
 * @file
 * Contains Drupal\maestro\Form\MaestroTemplateDeleteForm.
 */

namespace Drupal\maestro\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;

/**
 * Class MaestroTemplateDeleteForm.
 *
 * @package Drupal\maestro\Form
 *
 * @ingroup maestro
 */
class MaestroTemplateDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //let's see if there's any open processes using this template and tell the user that there's open processes that will be jettisoned.
    $count_warning = '';
    $query = \Drupal::entityQuery('maestro_process')
      ->condition('template_id', $this->entity->id);
    $res = $query->execute();
    $count = count($res);
    
    if($count >1 ) {
      return $this->t('<strong style="color: red; font-size: 1.2em;">Warning!</strong>  There are %count open processes attached to this Template.  
          Deleting this process will remove all associated Maestro data.  This action cannot be undone.', array(
        '%count' => $count ));
    }
    elseif($count == 1) {
      return $this->t('<strong style="color: red; font-size: 1.2em;">Warning!</strong>  There is %count open process attached to this Template.  
          Deleting this process will remove all associated Maestro data.  This action cannot be undone.', array(
        '%count' => $count ));
    }
    return $this->t('This action cannot be undone.');
  }
  
  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete Template %label?', array(
      '%label' => $this->entity->label()
    ));
  }

  /**
   * Gather the confirmation text.
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Delete Template');
  }

  /**
   * Gets the cancel route.
   *
   * @return Url
   *   
   */
  public function getCancelUrl() {
    return new Url('entity.maestro_template.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //delete all open processes with this template
    $query = \Drupal::entityQuery('maestro_process')
      ->condition('template_id', $this->entity->id);
    $entityIDs = $query->execute();
    foreach($entityIDs as $processID) {
      MaestroEngine::deleteProcess($processID);
    }
    
    
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message(t('Template %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirect('entity.maestro_template.list');
  }

}
