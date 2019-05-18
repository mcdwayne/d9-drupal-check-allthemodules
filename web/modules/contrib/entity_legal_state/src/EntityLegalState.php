<?php

namespace Drupal\entity_legal_state;

use Drupal\Core\State\StateInterface;
use Drupal\entity_legal\EntityLegalDocumentInterface;

/**
 * EntityLegalState service.
 *
 * Stores entity_legal published version config in state rather than config
 * entities.
 *
 * @package Drupal\entity_legal_state
 */
class EntityLegalState implements EntityLegalStateInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * EntityLegalState constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateVersion(EntityLegalDocumentInterface $legal_document) {
    return $this->state->get('entity_legal_state.' . $legal_document->id());
  }

  /**
   * {@inheritdoc}
   */
  public function updateStateVersion(EntityLegalDocumentInterface $legal_document, $version_value) {
    $this->state->set('entity_legal_state.' . $legal_document->id(), $version_value);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteStateVersion(EntityLegalDocumentInterface $legal_document) {
    $this->state->delete('entity_legal_state.' . $legal_document->id());
  }

  /**
   * {@inheritdoc}
   */
  public function updatePublishedVersion(EntityLegalDocumentInterface $legal_document) {
    $state_value = $this->getStateVersion($legal_document);
    $entity_value = $legal_document->get('published_version');
    if ($state_value != $entity_value) {
      $this->updateStateVersion($legal_document, $entity_value);
    }
  }

}
