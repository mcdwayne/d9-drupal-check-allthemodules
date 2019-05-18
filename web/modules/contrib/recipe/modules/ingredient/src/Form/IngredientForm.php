<?php

namespace Drupal\ingredient\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ingredient edit forms.
 */
class IngredientForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('ingredient.admin');
    $entity = $this->getEntity();
    $entity->save();
  }

}
