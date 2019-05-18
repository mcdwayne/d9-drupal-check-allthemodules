<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * The confirmation form for deleting an individual.
 */
class IndividualDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The individual %name (%id) has been deleted.', [
      '%id' => $entity->id(),
      '%name' => $entity->label(),
    ]);
  }

}
