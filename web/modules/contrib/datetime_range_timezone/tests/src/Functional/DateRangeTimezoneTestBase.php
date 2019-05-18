<?php

namespace Drupal\Tests\datetime_range_timezone\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\datetime_range_timezone\Kernel\DateRangeTimezoneHelperTrait;

/**
 * Base class for testing.
 */
abstract class DateRangeTimezoneTestBase extends BrowserTestBase {

  use DateRangeTimezoneHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'datetime_range_timezone'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setupDatetimeRangeTimezoneField();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]));
  }

}
