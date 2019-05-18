<?php

namespace Drupal\conflict\Form;

use Drupal\conflict\Entity\EntityConflictHandlerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class ConflictResolutionInlineFormBuilder {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConflictResolutionFormBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Adds the conflict resolution overview to the form.
   *
   * @param $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of the form.
   */
  public function processForm(&$form, FormStateInterface $form_state, EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    /** @var \Drupal\conflict\Entity\EntityConflictHandlerInterface $entity_conflict_resolution_handler */
    $entity_conflict_resolution_handler = $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'conflict.resolution_handler');

    $entity_local_original = $entity->{EntityConflictHandlerInterface::CONFLICT_ENTITY_ORIGINAL};
    $entity_server = $entity->{EntityConflictHandlerInterface::CONFLICT_ENTITY_SERVER};
    $conflicts = $entity_conflict_resolution_handler->getConflicts($entity_local_original, $entity, $entity_server);

    foreach ($conflicts as $field_name => $conflict_type) {
      $form[$field_name]['conflict_resolution'] = [
        '#type' => 'details',
        '#title' => $entity->get($field_name)->getFieldDefinition()->getLabel() . ' - ' . $this->t('Conflict resolution'),
        '#open' => TRUE,
      ];
      $form[$field_name]['conflict_resolution']['overview'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Local version'),
          $this->t('Initial version'),
          $this->t('Server version'),
        ],
        '#rows' => [[
            ['data' => $entity->get($field_name)->view()],
            ['data' => $entity_local_original->get($field_name)->view()],
            ['data' => $entity_server->get($field_name)->view()],
        ]],
      ];
      $form[$field_name]['conflict_resolution']['confirm'] = [
        '#type' => 'checkbox',
        '#required' => TRUE,
        '#title' => $this->t('Manual merge completed'),
      ];
    }

    $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'conflict.resolution_handler')
      ->finishConflictResolution($entity, [], $form_state);


    // Ensure the form will not be flagged for rebuild.
    // @see \Drupal\conflict\Entity\ContentEntityConflictHandler::entityMainFormValidateLast().
    $form_state->set('conflict.paths', []);

    $message = $this->t('The content has either been modified by another user, or you have already submitted modifications. Manual merge of the conflicts is required.');
    $form['#attached']['drupalSettings']['conflict']['inlineResolutionMessage'] = (string) $message;

    $form['#attached']['library'][] = 'conflict/drupal.conflict_resolution';
  }

}
