<?php

namespace Drupal\entity_switcher\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the entity switcher instance
 * deletion form.
 */
class SwitcherDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.entity_switcher_setting.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The @entity-type %label has been removed.', [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]);
  }

}
