<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Interface for Scheduled Transitions.
 */
interface ScheduledTransitionInterface extends ContentEntityInterface {

  /**
   * Get the entity this scheduled transition is for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getEntity(): ?EntityInterface;

  /**
   * Get the revision this scheduled transition is for.
   *
   * @return string|int|null
   *   The revision ID.
   */
  public function getEntityRevisionId();

  /**
   * Get the language of the revision this scheduled transition is for.
   *
   * @return string|null
   *   The revision language code.
   */
  public function getEntityRevisionLanguage(): ?string;

  /**
   * Get the author for this scheduled transition.
   *
   * @return \Drupal\user\UserInterface|null
   *   The author.
   */
  public function getAuthor(): ?UserInterface;

  /**
   * Get the workflow for this scheduled transition.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   The workflow.
   */
  public function getWorkflow(): ?WorkflowInterface;

  /**
   * Get the new state for this scheduled transition.
   *
   * @return string|null
   *   The state ID.
   */
  public function getState(): string;

  /**
   * Get the time this scheduled transition was created.
   *
   * @return int
   *   The creation time.
   */
  public function getCreatedTime(): int;

  /**
   * Get the time this scheduled transition should execute.
   *
   * @return int
   *   The scheduled transition time.
   */
  public function getTransitionTime(): int;

  /**
   * Sets the lock time.
   *
   * @param int $time
   *   The lock time.
   *
   * @return static
   *   Returns entity for chaining.
   */
  public function setLockedOn(int $time);

  /**
   * Get the options.
   *
   * @return array
   *   An array of options.
   */
  public function getOptions(): array;

}
