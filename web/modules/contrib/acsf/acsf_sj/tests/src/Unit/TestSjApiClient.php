<?php

namespace Drupal\Tests\acsf_sj\Unit;

use Drupal\acsf_sj\Api\SjApiClient;

/**
 * Provides a Scheduled Jobs API client.
 */
class TestSjApiClient extends SjApiClient {

  public $execTest;

  /**
   * Override parent::exec().
   */
  protected function exec($command) {
    $this->execTest = $command;
  }

}
