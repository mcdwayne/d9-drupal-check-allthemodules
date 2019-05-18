<?php

namespace Drupal\Tests\date_recur_ss\Unit;

use Drupal\date_recur_ss\Plugin\DateRecurInterpreter\SsInterpreter;
use Drupal\date_recur\Rl\RlDateRecurRule;
use Drupal\Tests\UnitTestCase;

/**
 * Tests SShaun implementation of interpreter.
 *
 * Interpretations come from the SShaun library, test the basics here.
 *
 * @coversDefaultClass \Drupal\date_recur_ss\Plugin\DateRecurInterpreter\SsInterpreter
 * @group date_recur_ss
 *
 * @ingroup SShaunPhpRrule
 */
class DateRecurSsInterpretationUnitTest extends UnitTestCase {

  /**
   * Tests secondly interpretation.
   */
  public function testSecondly() {
    $parts = [
      'FREQ' => 'SECONDLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYSECOND' => '59',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    // SShaun lib cannot handle frequencies lower than daily.
    $this->assertEquals('Unable to fully convert this rrule to text.', $interpretation);
  }

  /**
   * Tests minutely interpretation.
   */
  public function testMinutely() {
    $parts = [
      'FREQ' => 'MINUTELY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMINUTE' => '44',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    // SShaun lib cannot handle frequencies lower than daily.
    $this->assertEquals('Unable to fully convert this rrule to text.', $interpretation);
  }

  /**
   * Tests hourly interpretation.
   */
  public function testHourly() {
    $parts = [
      'FREQ' => 'HOURLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYHOUR' => '4,7',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');

    // SShaun lib cannot handle frequencies lower than daily.
    $this->assertEquals('Unable to fully convert this rrule to text.', $interpretation);
  }

  /**
   * Tests daily interpretation.
   */
  public function testDaily() {
    $parts = [
      'FREQ' => 'DAILY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'WE,SU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('daily on Wednesday and Sunday', $interpretation);
  }

  /**
   * Tests weekly interpretation.
   */
  public function testWeekly() {
    $parts = [
      'FREQ' => 'WEEKLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'MO,TU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('weekly on Monday and Tuesday', $interpretation);
  }

  /**
   * Tests monthly interpretation.
   */
  public function testMonthly() {
    $parts = [
      'FREQ' => 'MONTHLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMONTH' => '2,10',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('every February and October', $interpretation);
  }

  /**
   * Tests yearly interpretation.
   */
  public function testYearly() {
    $parts = [
      'FREQ' => 'YEARLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = new SsInterpreter([], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('yearly on July 15', $interpretation);
  }

}
