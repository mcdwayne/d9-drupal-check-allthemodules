<?php

namespace Drupal\competition;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Competition entities.
 */
interface CompetitionInterface extends ConfigEntityInterface {

  /**
   * Competition status - Open.
   */
  const STATUS_OPEN = 0x01;

  /**
   * Competition status - Closed.
   */
  const STATUS_CLOSED = 0x02;

  /**
   * {@inheritdoc}
   */
  public function id();

  /**
   * {@inheritdoc}
   */
  public function getStatus();

  /**
   * {@inheritdoc}
   */
  public function getLabel();

  /**
   * {@inheritdoc}
   */
  public function getCycle();

  /**
   * Retrieve all cycles in this competition that are configured as 'archived'.
   *
   * @return array
   *   Array in which both keys and values are cycle keys (not labels).
   *
   * @see CompetitionForm::form()
   * @see CompetitionForm::save()
   */
  public function getCyclesArchived();

  /**
   * {@inheritdoc}
   */
  public function getEntryLimits();

  /**
   * {@inheritdoc}
   */
  public function getLongtext();

}
