<?php

namespace Drupal\Tests\healthcheck\Unit;

use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the FindingStatic utility class.
 *
 * @group healthcheck
 *
 * @coversDefaultClass \Drupal\healthcheck\Finding\FindingStatus
 */
class FindingStatusTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Nothing to do here.
    parent::setUp();
  }

  /**
   * Tests the constant to numeric method.
   *
   * @covers ::constantToNumeric
   */
  public function testConstantToNumeric() {
    $this->assertEquals(FindingStatus::NOT_PERFORMED, FindingStatus::constantToNumeric('finding_not_performed'));

    $this->assertEquals(FindingStatus::NO_ACTION_REQUIRED, FindingStatus::constantToNumeric('finding_no_action_required'));

    $this->assertEquals(FindingStatus::NEEDS_REVIEW, FindingStatus::constantToNumeric('finding_needs_review'));

    $this->assertEquals(FindingStatus::ACTION_REQUESTED, FindingStatus::constantToNumeric('finding_action_requested'));

    $this->assertEquals(FindingStatus::CRITICAL, FindingStatus::constantToNumeric('finding_critical'));

    $this->assertFalse(FindingStatus::constantToNumeric('blargle_blargle_blah'));
  }

  /**
   * Tests the numeric to constant method.
   *
   * @covers ::numericToConstant
   */
  public function testNumericToConstant() {
    $this->assertEquals(FindingStatus::numericToConstant(FindingStatus::NOT_PERFORMED), 'finding_not_performed');

    $this->assertEquals(FindingStatus::numericToConstant(FindingStatus::NO_ACTION_REQUIRED), 'finding_no_action_required');

    $this->assertEquals(FindingStatus::numericToConstant(FindingStatus::NEEDS_REVIEW), 'finding_needs_review');

    $this->assertEquals(FindingStatus::numericToConstant(FindingStatus::ACTION_REQUESTED), 'finding_action_requested');

    $this->assertEquals(FindingStatus::numericToConstant(FindingStatus::CRITICAL), 'finding_critical');

    $this->assertFalse(FindingStatus::numericToConstant(-9999));
  }
}
