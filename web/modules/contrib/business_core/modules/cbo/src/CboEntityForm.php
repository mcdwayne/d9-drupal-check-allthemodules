<?php

namespace Drupal\cbo;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the CBO edit forms.
 */
class CboEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $insert = $entity->isNew();
    $entity->save();

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getLabel();
    $entity_link = $entity->link($this->t('View'));
    $context = [
      '%entity_type_label' => $entity_type_label,
      '%title' => $entity->label(),
      'link' => $entity_link,
    ];
    $t_args = [
      '%entity_type_label' => $entity_type_label,
      '%title' => $entity->link($entity->label()),
    ];

    if ($insert) {
      $this->logger($entity_type_id)->notice('%entity_type_label: added %title.', $context);
      drupal_set_message($this->t('%entity_type_label %title has been created.', $t_args));
    }
    else {
      $this->logger($entity_type_id)->notice('%entity_type_label: updated %title.', $context);
      drupal_set_message($this->t('%entity_type_label %title has been updated.', $t_args));
    }
  }

}