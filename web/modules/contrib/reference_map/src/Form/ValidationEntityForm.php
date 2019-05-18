<?php

namespace Drupal\reference_map\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Entity form variant for validation entity types.
 */
class ValidationEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\Core\Entity\ConfigEntityInterface $entity */
    $entity = $this
      ->buildEntity($form, $form_state);
    $violations = $entity
      ->validate();

    // Set the errors on the form.
    foreach ($violations as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state
        ->setErrorByName(str_replace('.', '][', $violation
          ->getPropertyPath()), $violation
          ->getMessage());
    }

    // The entity was validated.
    $entity
      ->setValidationRequired(FALSE);
    $form_state
      ->setTemporaryValue('entity_validated', TRUE);
    return $entity;
  }

}
