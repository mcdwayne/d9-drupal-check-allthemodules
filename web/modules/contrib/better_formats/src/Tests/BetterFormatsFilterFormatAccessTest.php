<?php

namespace Drupal\better_formats\Tests;

use Drupal\Tests\filter\Functional\FilterFormatAccessTest;

/**
 * Copy of FilterFormatAccessTest.
 *
 * @group better_formats
 */
class BetterFormatsFilterFormatAccessTest extends FilterFormatAccessTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['better_formats'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
  }
}
