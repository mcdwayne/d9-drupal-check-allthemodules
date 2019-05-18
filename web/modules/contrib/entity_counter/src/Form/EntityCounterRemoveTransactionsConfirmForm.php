<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\entity_counter\Entity\CounterTransaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for removing all entity counter transactions.
 */
class EntityCounterRemoveTransactionsConfirmForm extends EntityDeleteForm {

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * EntityCounterRemoveTransactionsConfirmForm constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state system.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

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
    return $this->t('Are you sure you want to remove the @entity-type %label transactions?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();

    return $this->t('The @entity-type %label transactions has been removed.', [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $items = $this->entityTypeManager
      ->getStorage('entity_counter_transaction')
      ->getQuery()
      ->condition('entity_counter.target_id', $this->getEntity()->id())
      ->execute();

    CounterTransaction::deleteTransactionsBatch($items);

    $this->state->delete('entity_counter.' . $this->getEntity()->id());
    $this->messenger()->addStatus($this->getDeletionMessage());
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
