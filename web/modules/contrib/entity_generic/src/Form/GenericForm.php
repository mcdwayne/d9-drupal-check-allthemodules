<?php

namespace Drupal\entity_generic\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for entity forms.
 */
class GenericForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();
    drupal_set_message($this->t('The entity %label has been successfully saved.', ['%label' => $entity->label()]));
    $form_state->setRedirect('entity.' . $entity->getEntityTypeId() . '.canonical', [$entity->getEntityTypeId() => $entity->id()]);
  }

}
