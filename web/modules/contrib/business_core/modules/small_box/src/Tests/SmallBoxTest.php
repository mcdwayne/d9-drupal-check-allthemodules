<?php

namespace Drupal\small_box\Tests;

/**
 * Tests small_box module.
 *
 * @group small_box
 */
class SmallBoxTest extends SmallBoxTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * Test small box module.
   */
  function testSmallBox() {
    $this->drupalPlaceBlock('small_box_block', [
      'region' => 'header',
      'id' => 'small_box_block',
      'content' => 'SMALL_BOX_CONTENT',
    ]);
    $this->drupalGet('');
    $this->assertText('SMALL_BOX_CONTENT');
  }

}
