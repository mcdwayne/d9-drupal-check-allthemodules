<?php

namespace Drupal\nivo_slider\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for testing the Nivo Slider module.
 */
abstract class NivoSliderTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['libraries', 'nivo_slider'];

  /**
   * Public function setUp.
   */
  public function setUp() {
    parent::setUp();

    // Create an admin user, log in and enable test blocks.
    $this->admin_user = $this->drupalCreateUser([
      'administer blocks', 'access administration pages', 'administer nivo slider',
    ]);
    $this->drupalLogin($this->admin_user);

    // Enable our test block.
    $this->block = $this->drupalPlaceBlock('nivo_slider_slider_block');
  }

  /**
   * Retrieves a sample image file.
   */
  public function getTestImage() {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles('image'));

    return $file;
  }

}
