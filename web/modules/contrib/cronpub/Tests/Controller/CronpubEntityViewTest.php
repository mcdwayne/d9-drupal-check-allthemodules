<?php

/**
 * @file
 * Contains \Drupal\cronpub\Tests\CronpubEntityView.
 */

namespace Drupal\cronpub\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the cronpub module.
 */
class CronpubEntityViewTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "cronpub CronpubEntityView's controller functionality",
      'description' => 'Test Unit for module cronpub and controller CronpubEntityView.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests cronpub functionality.
   */
  public function testCronpubEntityView() {
    // Check that the basic functions of module cronpub.
  }

}
