<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\WebformSubmissionChangeHistory\Change;
use PHPUnit\Framework\TestCase;

/**
 * Test Change.
 *
 * @group myproject
 */
class ChangeTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(Change::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
