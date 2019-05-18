<?php

/**
 * @file
 * Contains \Drupal\maestro\MaestroExecuteInteractive
 */

namespace Drupal\maestro\Form;

use Drupal\Core\Form\FormStateInterface;


/**
 * Interactive task form handler
 */
class MaestroExecuteInteractive extends MaestroInteractiveFormBase {

  /**
   * Overridden method to build the form for the interactive task.
   * We are fetching off the interactive function's form fields for this task.
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queueid = NULL, $modal = NULL) {
    //the parent to this method does some special things... like makes sure we are the one assigned
    //to execute this task.  
    $form = parent::buildForm($form, $form_state, $queueid, $modal);
    if(isset($form['error'])) {
      return $form;
    }
    
    return $this->getExecutableFormFields();
  }

  
  
}
