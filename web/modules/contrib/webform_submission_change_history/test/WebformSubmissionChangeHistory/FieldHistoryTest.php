<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\WebformSubmissionChangeHistory\FieldHistory;
use PHPUnit\Framework\TestCase;

/**
 * Test FieldHistory.
 *
 * @group myproject
 */
class FieldHistoryTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(FieldHistory::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
