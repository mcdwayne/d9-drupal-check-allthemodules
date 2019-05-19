<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSet;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\webform_scheduled_tasks\Iterator\WebformIteratorAggregate;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get all submissions completed since the last successful run of the task.
 *
 * @WebformScheduledResultSet(
 *   id = "submissions_completed_since_last_success",
 *   label = @Translation("Submissions completed since last success"),
 * )
 */
class SubmissionsCompletedSinceLastSuccess extends ResultSetPluginBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time interface.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Key used for accessing status information from state.
   */
  const STATE_KEY = 'webform_scheduled_tasks.submissions_completed_since_last_success';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('webform_submission')->getQuery(),
      $container->get('state'),
      $container->get('datetime.time')
    );
  }

  /**
   * TaskPluginBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryInterface $submissionQuery, StateInterface $state, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $submissionQuery);
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultSet() {
    $this->initializeQueryDefaults();

    // If we have a value for a previously successful run of this scheduled task
    // filter results that were completed after the last.
    $success_map = $this->state->get(static::STATE_KEY, []);
    if (isset($success_map[$this->getScheduledTask()->id()])) {
      // Find all submissions that were completed on or after the request time
      // of last successful task run.
      $this->submissionQuery->condition('completed', $success_map[$this->getScheduledTask()->id()], '>=');
      // Restrict the set of submissions to those created before this request
      // started. If they were made between the start of the request and time
      // taken to execute the query, they will be included in the next scheduled
      // run due to the >= condition above.
      $this->submissionQuery->condition('completed', $this->time->getRequestTime(), '<');
    }

    return WebformIteratorAggregate::createFromQuery($this->submissionQuery)->getIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    // If the whole scheduled task was successful, store the time when the
    // latest submission was made, so we can query.
    $success_map = $this->state->get(static::STATE_KEY, []);
    $success_map[$this->getScheduledTask()->id()] = $this->time->getRequestTime();
    $this->state->set(static::STATE_KEY, $success_map);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSummary() {
    return $this->t('All submissions since the last successful run of this task will be included.');
  }

}
