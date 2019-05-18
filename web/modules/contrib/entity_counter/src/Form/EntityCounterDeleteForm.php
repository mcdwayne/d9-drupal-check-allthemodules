<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_counter\Entity\CounterTransaction;

/**
 * Provides a deletion confirmation form for the entity counter.
 */
class EntityCounterDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.entity_counter.collection');
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $items = $this->entityTypeManager
      ->getStorage('entity_counter')
      ->getQuery()
      ->condition('entity_counter.target_id', $this->getEntity()->id())
      ->execute();

    CounterTransaction::deleteTransactionsBatch($items);

    parent::submitForm($form, $form_state);
  }

}
