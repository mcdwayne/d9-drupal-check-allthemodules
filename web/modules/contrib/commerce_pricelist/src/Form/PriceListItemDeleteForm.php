<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

class PriceListItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label price?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The %label price has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    $entity = $this->getEntity();
    $this->logger($entity->getEntityType()->getProvider())->notice('The %label price has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

}
