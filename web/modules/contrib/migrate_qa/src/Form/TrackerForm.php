<?php

namespace Drupal\migrate_qa\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\RevisionLogInterface;

class TrackerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    // Always hide the current revision log message in UI.
    if ($this->entity instanceof RevisionLogInterface) {
      $this->entity->setRevisionLogMessage(NULL);
    }
  }

}
