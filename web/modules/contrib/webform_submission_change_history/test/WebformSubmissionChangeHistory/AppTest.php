<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\WebformSubmissionChangeHistory\App;
use PHPUnit\Framework\TestCase;

/**
 * Test App.
 *
 * @group myproject
 */
class AppTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(App::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
