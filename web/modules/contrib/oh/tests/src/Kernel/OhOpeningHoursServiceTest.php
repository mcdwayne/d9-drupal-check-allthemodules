<?php

namespace Drupal\Tests\oh\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOpeningHours;
use Drupal\oh\OhOpeningHoursInterface;

/**
 * Tests opening hours service.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh\OhOpeningHours
 */
class OhOpeningHoursServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user', 'oh_test', 'oh'];

  /**
   * Tests exception if a regular subscriber produces occurrences out of range.
   */
  public function testOutOfRange() {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $this->setRegularScenarios(['every_day_2015']);

    $start = new DrupalDateTime('1 Jan 2016 00:00');
    $end = new DrupalDateTime('31 Dec 2016 00:00');
    $range = new OhDateRange($start, $end);

    $this->setExpectedException(\Exception::class, 'Inner date starts before outer date.');
    $this->openingHoursService()->getOccurrences($entity, $range);
  }

  /**
   * Tests regular occurrences.
   */
  public function testRegularOccurrences() {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $this->setRegularScenarios(['every_day_2015']);

    $start = new DrupalDateTime('1 Jan 2015 00:00');
    $end = new DrupalDateTime('1 Jan 2016 00:00');
    $range = new OhDateRange($start, $end);

    $occurrences = $this->openingHoursService()->getOccurrences($entity, $range);
    $this->assertCount(365, $occurrences);

    $days = $this->groupByDays($occurrences);
    $this->assertCount(365, $days);

    // Ensure there is just one occurrence on any particular day.
    $this->assertCount(1, $days['2015-01-01']);
  }

  /**
   * Tests exception occurrences.
   */
  public function testExceptionOccurrences() {
    // Time format. E.g: 09:01:59.
    $timeFormat = 'H:i:s';

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $this->setRegularScenarios(['every_day_2015']);
    $this->setExceptionScenarios(['mondays_2015']);

    $start = new DrupalDateTime('1 Jan 2015 00:00');
    $end = new DrupalDateTime('1 Jan 2016 00:00');
    $range = new OhDateRange($start, $end);

    $occurrences = $this->openingHoursService()->getOccurrences($entity, $range);
    $this->assertCount(365, $occurrences);

    $days = $this->groupByDays($occurrences);
    $this->assertCount(365, $days);

    // 1 March 2015 is a Sunday.
    $this->assertEquals('09:00:00', $days['2015-03-01'][0]->getStart()->format($timeFormat));
    $this->assertEquals('00:00:00', $days['2015-03-02'][0]->getStart()->format($timeFormat));
    $this->assertEquals('Mondays are closed', $days['2015-03-02'][0]->getMessage());
  }

  /**
   * Tests regular occurrences.
   *
   * All regular occurrences are shown since exceptions are not applied.
   */
  public function testRegularOnly() {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $this->setRegularScenarios(['every_day_2015']);
    $this->setExceptionScenarios(['mondays_2015']);

    $start = new DrupalDateTime('1 Jan 2015 00:00');
    $end = new DrupalDateTime('1 Jan 2016 00:00');
    $range = new OhDateRange($start, $end);

    $occurrences = $this->openingHoursService()->getRegularHours($entity, $range);

    // All regular occurrences.
    $this->assertCount(365, $occurrences);
  }

  /**
   * Tests exception occurrences.
   */
  public function testExceptionOnly() {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $this->setRegularScenarios(['every_day_2015']);
    $this->setExceptionScenarios(['mondays_2015']);

    $start = new DrupalDateTime('1 Jan 2015 00:00');
    $end = new DrupalDateTime('1 Jan 2016 00:00');
    $range = new OhDateRange($start, $end);

    $occurrences = $this->openingHoursService()->getExceptions($entity, $range);

    // There are 52 Mondays in 2015.
    $this->assertCount(52, $occurrences);
  }

  /**
   * Opening hours service.
   *
   * @return \Drupal\oh\OhOpeningHoursInterface
   *   The opening hours service.
   */
  protected function openingHoursService(): OhOpeningHoursInterface {
    return $this->container->get('oh.opening_hours');
  }

  /**
   * Set the scenarios for regular test service.
   *
   * @param array $scenarios
   *   A list of scenarios.
   */
  protected function setRegularScenarios(array $scenarios): void {
    $this->container->get('oh_test.regular')
      ->setScenarios($scenarios);
  }

  /**
   * Set the scenarios for exception test service.
   *
   * @param array $scenarios
   *   A list of scenarios.
   */
  protected function setExceptionScenarios(array $scenarios): void {
    $this->container->get('oh_test.exceptions')
      ->setScenarios($scenarios);
  }

  /**
   * Utility to group occurrences by day.
   *
   * @param iterable|\Drupal\oh\OhOccurrence[] $occurrences
   *   A list of occurrences.
   *
   * @return array
   *   Occurrences grouped by days.
   */
  protected function groupByDays(iterable $occurrences): array {
    $days = [];
    foreach ($occurrences as $occurrence) {
      $days[$occurrence->getStart()->format(OhOpeningHours::DAY_FORMAT)][] = $occurrence;
    }
    return $days;
  }

}
