<?php

namespace Drupal\Tests\oh\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oh\OhDateRange;

/**
 * Tests OhDateRange class.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh\OhDateRange
 */
class OhDateRangeTest extends KernelTestBase {

  /**
   * Test message default value.
   */
  public function testRequiredConstructors() {
    $this->setExpectedException(\ArgumentCountError::class);
    $this->createDateRange();
  }

  /**
   * Tests start and end getters.
   *
   * @covers ::getStart
   * @covers ::getEnd
   */
  public function testGetters() {
    $start = new DrupalDateTime('yesterday');
    $end = new DrupalDateTime('tomorrow');
    $dateRange = $this->createDateRange($start, $end);

    $this->assertEquals($start, $dateRange->getStart());
    $this->assertEquals($end, $dateRange->getEnd());
  }

  /**
   * Tests same time zone validation.
   *
   * @covers ::validateDates
   */
  public function testTimeZoneValidation() {
    $start = new DrupalDateTime('yesterday', 'Australia/Sydney');
    $end = new DrupalDateTime('tomorrow', 'Australia/Sydney');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    // Change the timezone.
    $end = new DrupalDateTime('tomorrow', 'Australia/Perth');
    $this->setExpectedException(\InvalidArgumentException::class, 'Provided dates must be in same timezone.');
    $this->createDateRange($start, $end);
  }

  /**
   * Tests end occur on or after start.
   *
   * @covers ::validateDates
   */
  public function testEndAfterStartValidation() {
    // Same time.
    $start = new DrupalDateTime('Monday 12:00:00');
    $end = new DrupalDateTime('Monday 12:00:00');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    // End after start.
    $start = new DrupalDateTime('Monday 12:00:00');
    $end = new DrupalDateTime('Monday 12:00:01');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    $start = new DrupalDateTime('Monday 12:00:01');
    $end = new DrupalDateTime('Monday 12:00:00');

    $this->setExpectedException(\InvalidArgumentException::class, 'End date must not occur before start date.');
    $this->createDateRange($start, $end);
  }

  /**
   * Tests object sorting.
   *
   * @covers ::sort
   */
  public function testSort() {
    /** @var \Drupal\oh\OhDateRange[] $ranges */
    $ranges = [];

    $start1 = new DrupalDateTime('1 Jan 2016 12:00:00');
    $end1 = new DrupalDateTime('1 Jan 2018 12:00:00');
    $ranges[] = $this->createDateRange($start1, $end1);

    $start2 = new DrupalDateTime('1 Jan 2017 12:00:00');
    $end2 = new DrupalDateTime('1 Jan 2019 12:00:00');
    $ranges[] = $this->createDateRange($start2, $end2);

    $start3 = new DrupalDateTime('1 Jan 2015 12:00:00');
    $end3 = new DrupalDateTime('1 Jan 2017 12:00:00');
    $ranges[] = $this->createDateRange($start3, $end3);

    usort($ranges, [OhDateRange::class, 'sort']);
    $this->assertEquals($start3, $ranges[0]->getStart());
    $this->assertEquals($start1, $ranges[1]->getStart());
    $this->assertEquals($start2, $ranges[2]->getStart());
  }

  /**
   * Tests isWithin utility
   *
   * @covers ::isWithin
   */
  public function testIsWithin() {
    $outerStart = new DrupalDateTime('1 January 2016');
    $outerEnd = new DrupalDateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new DrupalDateTime('1 March 2016');
    $innerEnd = new DrupalDateTime('31 October 2016');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->assertTrue($outerRange->isWithin($innerRange));

    // Test same.
    // Dates with the exact same start and end time are permitted.
    $innerRange = $outerRange;
    $this->assertTrue($outerRange->isWithin($innerRange));
  }

  /**
   * Tests isWithin utility inner-start starts before outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerStartBeforeOuterStart() {
    $outerStart = new DrupalDateTime('1 January 2016');
    $outerEnd = new DrupalDateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new DrupalDateTime('1 March 2015');
    $innerEnd = new DrupalDateTime('31 October 2016');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date starts before outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Tests isWithin utility inner-start starts after outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerStartAfterOuterEnd() {
    $outerStart = new DrupalDateTime('1 January 2016');
    $outerEnd = new DrupalDateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new DrupalDateTime('1 March 2017');
    $innerEnd = new DrupalDateTime('31 October 2017');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date starts after outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Tests isWithin utility inner-end ends after outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerEndAfterOuterEnd() {
    $outerStart = new DrupalDateTime('1 January 2016');
    $outerEnd = new DrupalDateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new DrupalDateTime('1 March 2016');
    $innerEnd = new DrupalDateTime('31 October 2017');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date ends after outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Create a new range.
   *
   * @param array $args
   *   Arguments to pass to constructor.
   *
   * @return \Drupal\oh\OhDateRange
   *   New range object.
   */
  protected function createDateRange(...$args) {
    return new OhDateRange(...$args);
  }

}
