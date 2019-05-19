<?php

namespace Drupal\state_form_entity\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for content type deletion.
 */
class StateFormEntityDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $form_state->setRedirect('entity.state_form_entity.collection');
    return parent::buildForm($form, $form_state);
  }

}
