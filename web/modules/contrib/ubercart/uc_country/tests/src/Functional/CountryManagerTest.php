<?php

namespace Drupal\Tests\uc_country\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of the extended CountryManager service.
 *
 * @group ubercart
 */
class CountryManagerTest extends BrowserTestBase {

  public static $modules = ['uc_country'];

  /**
   * Test overriding the core Drupal country_manager service.
   */
  public function testServiceOverride() {
    $country_manager = \Drupal::service('country_manager');

    /*
     * Test core Drupal country_manager functions.
     */

    // getList(): Verify that all Drupal-provided countries
    // were imported without error.
    $this->assertEquals(count($country_manager->getList()), 258, '258 core Drupal countries found');

    /*
     * Test new functions provided by this extended service.
     */

    // getAvailableList(): Verify that all Ubercart-provided countries
    // are available.
    $this->assertEquals(count($country_manager->getAvailableList()), 248, '248 Ubercart countries found');

    // getEnabledList(): Verify that no countries are enabled by default.
    $this->assertEquals(count($country_manager->getEnabledList()), 0, 'No Ubercart countries enabled by default');

    // getCountry(): Verify the basic get country function works and
    // returns a valid config entity.
    $country_manager->getCountry('US')->setStatus(TRUE)->save();

    // getByProperty(): Verify we can obtain country entities by property.
    $this->assertEquals(count($country_manager->getByProperty(['status' => TRUE])), 1, 'One country enabled by default');

    // getZoneList(): Verify that CA has 13 zones.
    $this->assertEquals(count($country_manager->getZoneList('CA')), 13, 'Canada has 13 zones');

    // @todo Compare getList() to core getStandardList().
    // @todo Test standard list alter, to make sure we don't break contrib.
  }

}
