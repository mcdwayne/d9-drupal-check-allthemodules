<?php

namespace Drupal\competition\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validation of entry limit for a Competition cycle.
 *
 * @Constraint(
 *   id = "CompetitionEntry",
 *   label = @Translation("CompetitionEntry", context = "Validation"),
 *   type = "entity:competition_entry"
 * )
 */
class CompetitionEntryConstraint extends CompositeConstraintBase {
  /**
   * Validation message strings.
   *
   * @var string
   */
  public $messageCompetitionClosed = 'The %cycle% %label% is no longer open.';

  public $messageLimitRequireUser = 'You must log in or register before entering.';

  public $messageLimitReached = 'You have already entered the %cycle% %label% @count@@interval@.';

  public $messageReentryInvalidField = 'No previous entry was found for @field@ "%value%" in the %cycle% %label%.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['type', 'cycle', 'uid'];
  }

}
