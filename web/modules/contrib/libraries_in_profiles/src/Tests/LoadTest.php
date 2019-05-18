<?php

namespace Drupal\libraries_in_profiles\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group libraries_in_profiles
 */
class LoadTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['libraries_in_profiles'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration', 'access administration pages']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * Tests that the config page loads with a 200 response.
   */
  public function testConfig() {
    $this->drupalGet(Url::fromRoute('libraries_in_profiles.locations_form'));
    $this->assertResponse(200);
  }

}
