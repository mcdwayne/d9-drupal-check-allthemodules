<?php

namespace Drupal\webform_submission_change_history\WebformSubmissionChangeHistory;

use Drupal\webform_submission_change_history\traits\CommonUtilities;

/**
 * Represents the history of changes for a submission's field.
 */
class FieldHistory {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param string $field
   *   The field name.
   */
  public function __construct(string $field) {
    $this->field = $field;
  }

  /**
   * Add a change to the list of changes.
   *
   * @param Change $change
   *   A historical change to this field.
   */
  public function addChange(Change $change) {
    $changes = $this->getChanges();
    $changes[] = $change;
    $this->changes = $changes;
  }

  /**
   * Getter for the list of changes.
   *
   * @return array
   *   Array of Change objects.
   */
  public function getChanges() : array {
    if (empty($this->changes)) {
      $this->changes = [];
    }
    return $this->changes;
  }

  /**
   * Getter for the field name.
   *
   * @return string
   *   The field name.
   */
  public function field() {
    return $this->field;
  }

  /**
   * Get a table render array for the change history for this field.
   *
   * @return array
   *   Array of render arrays. Empty array if no changes.
   *
   * @throws Exception
   */
  public function formatted() : array {
    $rows = $this->formattedRows();
    $return = [];
    if (count($rows)) {
      $return[$this->field() . '_info'] = [
        '#type' => 'table',
        '#header' => [t('Changed from'), t('Changed to'), t('User'), t('Date')],
        '#rows' => $this->formattedRows(),
      ];
    }
    return $return;
  }

  /**
   * Get rows to display changes for this field.
   *
   * @return array
   *   An array of rows to be passed to the table render array.
   *
   * @throws Exception
   */
  public function formattedRows() : array {
    $return = [];
    foreach ($this->getChanges() as $change) {
      $return[] = [
        $change->from(),
        $change->to(),
        $change->formattedUser(),
        $change->formattedDate(),
      ];
    }
    return $return;
  }

}
