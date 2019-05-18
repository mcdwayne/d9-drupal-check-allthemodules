<?php

namespace Drupal\field_tools\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delete form for field storage config entities.
 */
class FieldStorageConfigDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("This action cannot be undone. ALL the '@field-name' fields on the '@entity-type' entity type will be deleted!", [
      '@field-name' => $this->entity->getName(),
      '@entity-type' => $this->entity->getTargetEntityTypeId(),
    ]);
  }

}
