<?php

/**
 * Contains \Drupal\testswarm\Tests\TestswarmTest.
 */

namespace Drupal\testswarm\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides SimpleTest integration tests for the testswarm module.
 */
class TestswarmTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('testswarm');

  public static function getInfo() {
    return array(
      'name' => 'Testswarm integration tests',
      'description' => 'Provides PHP integration test coverage for Testswarm',
      'group' => 'Testswarm',
    );
  }

  /**
   * Tests the Testswarm admin page.
   */
  public function testAdminPage() {
    $admin_user = $this->drupalCreateUser(array('administer testswarm settings'));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/development/testswarm');
    $this->assertResponse('200');
    $this->assertTitle(t('TestSwarm | Drupal'));
  }

}
