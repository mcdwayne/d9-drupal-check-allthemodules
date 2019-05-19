<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSet;

use Drupal\webform_scheduled_tasks\Iterator\WebformIteratorAggregate;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginBase;

/**
 * A scheduled result set plugin to load all submissions for a form.
 *
 * @WebformScheduledResultSet(
 *   id = "all_submissions",
 *   label = @Translation("All completed submissions"),
 * )
 */
class AllSubmissions extends ResultSetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResultSet() {
    $this->initializeQueryDefaults();
    return WebformIteratorAggregate::createFromQuery($this->submissionQuery)->getIterator();
  }

  /**
   * {@inheritdoc}
   */
  protected function getSummary() {
    return $this->t('All submissions will be processed every time this scheduled task runs.');
  }

}
