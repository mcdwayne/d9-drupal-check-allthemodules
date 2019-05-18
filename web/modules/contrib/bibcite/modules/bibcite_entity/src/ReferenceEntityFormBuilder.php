<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;

/**
 * Builds reference forms.
 */
class ReferenceEntityFormBuilder extends EntityFormBuilder {

  /**
   * The form builder.
   *
   * @var \Drupal\bibcite_entity\ReferenceFormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = []) {
    if ($data = $this->formBuilder->restoreFromCache()) {
      $entity = $data;
    }
    return parent::getForm($entity, $operation, $form_state_additions);
  }

}
