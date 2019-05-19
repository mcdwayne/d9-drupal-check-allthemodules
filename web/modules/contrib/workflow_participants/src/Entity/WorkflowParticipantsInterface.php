<?php

namespace Drupal\workflow_participants\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Provides an interface for defining Workflow participants entities.
 *
 * @ingroup workflow_participants
 */
interface WorkflowParticipantsInterface extends ContentEntityInterface {

  /**
   * Gets the entity being moderated.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The moderated entity.
   */
  public function getModeratedEntity();

  /**
   * Get the editor IDs.
   *
   * @return int[]
   *   An array of editor IDs.
   */
  public function getEditorIds();

  /**
   * Get the editors for the item being moderated.
   *
   * @return \Drupal\user\UserInterface[]
   *   The editors.
   */
  public function getEditors();

  /**
   * Get the reviewer IDs.
   *
   * @return int[]
   *   An array of reviewer IDs.
   */
  public function getReviewerIds();

  /**
   * Get the reviewers for the item being moderated.
   *
   * @return \Drupal\user\UserInterface[]
   *   The reviewers.
   */
  public function getReviewers();

  /**
   * Determine if a user has access to the transition.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow associated with the entity.
   * @param \Drupal\workflows\TransitionInterface $transition
   *   The state transition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return bool
   *   Returns TRUE if the user can make the transition.
   *
   *   The workflow needs to be passed in since that is where the third party
   *   settings are stored for each transition.
   */
  public function userMayTransition(WorkflowInterface $workflow, TransitionInterface $transition, AccountInterface $account);

  /**
   * Determine if the user is an editor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current authenticated user.
   *
   * @return bool
   *   Returns TRUE if the user is an editor.
   */
  public function isEditor(AccountInterface $account);

  /**
   * Determine if the user is a reviewer.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current authenticated user.
   *
   * @return bool
   *   Returns TRUE if the user is a reviewer.
   */
  public function isReviewer(AccountInterface $account);

}
