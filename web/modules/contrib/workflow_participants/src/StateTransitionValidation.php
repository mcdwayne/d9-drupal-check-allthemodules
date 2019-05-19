<?php

namespace Drupal\workflow_participants;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Decorated state transition validation service.
 *
 * This overrides the base class to allow for access to transitions for
 * workflow participants.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The decorated service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $inner;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs the state transition validator.
   *
   * Since this is extending the decorated service, no inner service is needed.
   *
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $inner
   *   The inner service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(StateTransitionValidationInterface $inner, ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager) {
    $this->inner = $inner;
    $this->moderationInformation = $moderation_information;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $transitions = $this->inner->getValidTransitions($entity, $user);

    // In addition to those granted by content moderation, check for transitions
    // that allow editor/reviewer transitions.
    return $transitions + $this->getParticipantTransitions($entity, $user);
  }

  /**
   * Get valid transitions for editors and reviewers.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The moderated entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current authenticated user.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   The allowed state transitions.
   */
  protected function getParticipantTransitions(ContentEntityInterface $entity, AccountInterface $account) {
    if (!$entity->id()) {
      return [];
    }

    // This logic is copied from
    // Drupal\content_moderation\StateTransitionValidation::getValidTransitions.
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $current_state = $entity->moderation_state->value ? $workflow->getTypePlugin()->getState($entity->moderation_state->value) : $workflow->getTypePlugin()->getInitialState($entity);

    // Legal transitions include those that are possible from the current state,
    // filtered by those whose target is legal on this bundle and that the
    // user has access to execute.
    $participants = $this->entityTypeManager->getStorage('workflow_participants')->loadForModeratedEntity($entity);
    $transitions = array_filter($current_state->getTransitions(), function (TransitionInterface $transition) use ($workflow, $participants, $account) {
      return $participants->userMayTransition($workflow, $transition, $account);
    });

    return $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity = NULL) {
    // We can only make a determination if we have the entity, otherwise we
    // won't be able to reference the participants.
    if ($entity) {
      // As this may be occurring during validation, the moderation state on the
      // entity may be the new state, rather than the current state, so make
      // sure we're working with the current version.
      $original_entity = $entity->isNew() ? $entity : $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadRevision($entity->getLoadedRevisionId());
      $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($original_state->id(), $new_state->id());
      return in_array($transition->id(), array_keys($this->getValidTransitions($original_entity, $user)));
    }

    return FALSE;
  }

}
