<?php

namespace Drupal\webform_submission_change_history\WebformSubmissionChangeHistory;

use Drupal\webform_submission_change_history\traits\CommonUtilities;

/**
 * Represents historical information about a webform submission.
 */
class SubmissionInfo {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param int $id
   *   The submission ID.
   */
  public function __construct(int $id) {
    $this->id = $id;
  }

  /**
   * Alter the submission edit form.
   *
   * @param array $form
   *   The Drupal form to modify.
   *
   * @throws Exception
   */
  public function alterForm(&$form) {
    foreach ($this->fieldHistory() as $history) {
      $form['elements'] = $this->spliceAfterKey($form['elements'], $history->field(), $history->formatted());
    }
  }

  /**
   * Get history of changes for all fields.
   *
   * @return array
   *   Array of FieldHistory objects.
   *
   * @throws Exception
   */
  public function fieldHistory() : array {
    $all_changes = $this->getAllChanges();
    $return = [];
    foreach ($all_changes as $change) {
      if (empty($return[$change->field()])) {
        $return[$change->field()] = new FieldHistory($change->field());
      }
      $return[$change->field()]->addChange($change);
    }
    return $return;
  }

  /**
   * Get a log of all changes from the database.
   *
   * @return array
   *   An array of Change objects.
   *
   * @throws Exception
   */
  public function getAllChanges() : array {
    $query = \Drupal::database()->select('webform_submission_log', 'wsl');
    $query->addField('wsl', 'sid');
    $query->addField('wsl', 'uid');
    $query->addField('wsl', 'timestamp');
    $query->addField('wsl', 'data');
    $query->condition('wsl.operation', 'submission updated');
    $query->condition('wsl.sid', $this->id);

    return $this->processDbResult($query->execute()->fetchAll());
  }

  /**
   * Given a DB result, return a list of changes.
   *
   * @param array $result
   *   A result from the database.
   *
   * @return array
   *   An array of Change objects.
   *
   * @throws Exception
   */
  protected function processDbResult(array $result) : array {
    $return = [];
    foreach ($result as $row) {
      $unserialized = unserialize($row->data);
      if (isset($unserialized['changed'])) {
        foreach ($unserialized['changed'] as $key => $data) {
          if (array_key_exists('from', $data) && array_key_exists('to', $data)) {
            $return[] = new Change($key, $data['from'], $data['to'], $row->uid, $row->timestamp);
          }
        }
      }
    }
    return $return;
  }

}
