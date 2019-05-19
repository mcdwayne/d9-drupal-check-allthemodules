<?php

namespace Drupal\webform_submission_change_history\WebformSubmissionChangeHistory;

use Drupal\webform_submission_change_history\traits\CommonUtilities;

/**
 * Represents the history of changes for a submission's field.
 */
class Change {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param string $field
   *   The field name for this change.
   * @param mixed $from
   *   What this field was changed from.
   * @param mixed $to
   *   What this field was changed to.
   * @param int $user
   *   User id.
   * @param int $date
   *   Timestamp.
   */
  public function __construct($field, $from, $to, $user, $date) {
    $this->field = $field;
    $this->from = $from;
    $this->to = $to;
    $this->user = $user;
    $this->date = $date;
  }

  /**
   * Getter for "field".
   *
   * @return string
   *   The field this change pertains to.
   */
  public function field() {
    return $this->field;
  }

  /**
   * Getter for "from".
   *
   * @return mixed
   *   What this field has been changed from.
   */
  public function from() {
    return $this->from;
  }

  /**
   * Getter for "to".
   *
   * @return mixed
   *   What this field has been changed to.
   */
  public function to() {
    return $this->to;
  }

  /**
   * Get the username.
   *
   * @return string
   *   The username, or "Unknown".
   */
  public function formattedUser() {
    $user = user_load($this->user);
    if (!$user) {
      return t('Unknown');
    }
    else {
      return $user->getUsername();
    }
  }

  /**
   * Get the date, formatted.
   *
   * @return string
   *   The date, formatted in the "medium" format.
   */
  public function formattedDate() {
    return format_date($this->date);
  }

}
