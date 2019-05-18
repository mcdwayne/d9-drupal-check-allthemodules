<?php

namespace Drupal\formazing\FieldSettings;

use Drupal\Core\Form\FormStateInterface;

interface FieldInterface {

  /**
   * Get data from entity and render the form
   *
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $entity
   *
   * @return array
   */
  public function renderField($entity);

  /**
   * Get data from entity and render the form
   *
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $entity
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function saveField($entity, FormStateInterface $form_state);

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $field
   * @return array
   */
  public function parse($field);
}
