<?php

namespace Drupal\Tests\evergreen\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\evergreen\Entity\EvergreenContent;

/**
 * Tests the new entity API for evergreen content.
 *
 * @group evergreen
 * @SuppressWarnings(StaticAccess)
 */
class EvergreenContentTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['evergreen', 'user'];

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test EvergreenConfig::isEvergreen()
   */
  public function testIsEvergreen() {
    $content = EvergreenContent::create([
      'evergreen_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $this->assertTrue($content->isEvergreen(), 'Content should be evergreen');

    $content = EvergreenContent::create([
      'evergreen_status' => 0,
    ]);
    $this->assertFalse($content->isEvergreen(), 'Content should be evergreen');
  }

  /**
   * Test EvergreenConfig::isExpired()
   */
  public function testIsExpired() {
    // create a time to test against
    $one_week_ago = strtotime('-1 week');

    // create an expiry
    $expiry = 60 * 60;

    // make a content entity
    $content = EvergreenContent::create([
      'evergreen_status' => 0,
      'evergreen_reviewed' => $one_week_ago,
      // expire in an hour
      'evergreen_expiry' => $expiry,
      // expiration date is set based on the last review
      'evergreen_expires' => $one_week_ago + ($expiry),
    ]);
    $this->assertTrue($content->isExpired(), 'Content should be expired');

    // change the expiry to be 10 days
    $expiry = (60 * 60 * 24) * 10;
    $content = EvergreenContent::create([
      'evergreen_status' => 0,
      'evergreen_reviewed' => $one_week_ago,
      // expire in an hour
      'evergreen_expiry' => $expiry,
      // expiration date is set based on the last review
      'evergreen_expires' => $one_week_ago + $expiry,
    ]);
    $this->assertFalse($content->isExpired(), 'Content should not be expired');
  }

  /**
   * Test EvergreenContent::reviewed()
   */
  public function testReviewed() {
    $one_week_ago = strtotime('-1 week');
    $expiry = 60 * 60;
    $content = EvergreenContent::create([
      'evergreen_status' => 0,
      'evergreen_reviewed' => $one_week_ago,
      // expire in an hour
      'evergreen_expiry' => $expiry,
      // expiration date is set based on the last review
      'evergreen_expires' => $one_week_ago + ($expiry),
    ]);

    $content->reviewed();

    $this->assertFalse($content->isExpired(), 'Content should not be expired immediately after review');
    $this->assertEquals($expiry, $content->getEvergreenExpiry());
    $this->assertNotEquals($one_week_ago + $expiry, $content->getEvergreenExpires(), 'The expiration date should have changed');
  }

  /**
   * Test that evergreen content does not expire.
   */
  public function testEvergreenContentCannotExpire() {
    $content = EvergreenContent::create([
      'evergreen_status' => EVERGREEN_STATUS_EVERGREEN,
      'evergreen_expires' => strtotime('-1 month'),
      'evergreen_expiry' => 60,
    ]);
    $this->assertFalse($content->isExpired(), 'Evergreen content cannot expire');
  }

}
