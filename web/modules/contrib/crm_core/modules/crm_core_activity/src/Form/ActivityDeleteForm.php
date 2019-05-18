<?php

namespace Drupal\crm_core_activity\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * The confirmation form for deleting an activity.
 */
class ActivityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('@type %title has been deleted.', [
      '%id' => $entity->id(),
      '%title' => $entity->label(),
      '@type' => $entity->get('type')->entity->label(),
    ]);
  }

}
