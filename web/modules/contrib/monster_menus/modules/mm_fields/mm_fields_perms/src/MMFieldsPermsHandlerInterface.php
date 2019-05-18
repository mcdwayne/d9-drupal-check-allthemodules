<?php

namespace Drupal\mm_fields_perms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for providing content translation.
 *
 * Defines a set of methods to allow any entity to be processed by the entity
 * translation UI.
 */
interface MMFieldsPermsHandlerInterface {

  /**
   * Returns a set of field definitions to be used to store metadata items.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions();

  /**
   * Performs the needed alterations to the entity config form.
   *
   * @param array $form
   *   The entity form to be altered to provide the translation workflow.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being created or edited.
   */
  public function entityConfigFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity);

}
