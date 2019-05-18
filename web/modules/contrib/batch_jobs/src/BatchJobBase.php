<?php

namespace Drupal\batch_jobs;

/**
 * BatchJob base class.
 */
abstract class BatchJobBase {

  /**
   * The ID of the batch job.
   *
   * @var int
   */
  public $bid;

  /**
   * Retrieve a token for the batch job.
   *
   * @return string
   *   Job token.
   */
  public function getToken() {
    return \Drupal::csrfToken()->get($this->bid);
  }

}
