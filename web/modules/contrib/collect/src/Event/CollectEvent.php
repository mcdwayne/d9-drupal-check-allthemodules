<?php
/**
 * @file
 * Contains \Drupal\collect\Event\CollectEvent.
 */

namespace Drupal\collect\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired by the queue worker for a new Collect submission.
 *
 * @see \Drupal\collect\Plugin\QueueWorker\SubmissionProcessing
 */
class CollectEvent extends Event {

  /**
   * The processing event name.
   */
  const NAME = 'collect.process';

  /**
   * @var \Drupal\collect\CollectContainerInterface
   */
  protected $submission;

  /**
   * Constructs a CollectEvent object.
   *
   * @param \Drupal\collect\CollectContainerInterface $submission
   *   The submission to process.
   */
  public function __construct($submission) {
    $this->submission = $submission;
  }

  /**
   * Get the submission to process.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The collect container entity containing the submission data.
   */
  public function getSubmission() {
    return $this->submission;
  }
}
