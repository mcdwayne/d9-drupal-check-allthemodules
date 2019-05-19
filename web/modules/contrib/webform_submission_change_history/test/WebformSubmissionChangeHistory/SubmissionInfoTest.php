<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\WebformSubmissionChangeHistory\SubmissionInfo;
use PHPUnit\Framework\TestCase;

/**
 * Test SubmissionInfo.
 *
 * @group myproject
 */
class SubmissionInfoTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(SubmissionInfo::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
