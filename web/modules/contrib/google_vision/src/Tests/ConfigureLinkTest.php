<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests whether the configure link appears at the modules page.
 *
 * @group google_vision
 */
class ConfigureLinkTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'google_vision'];

  /**
   * A user with permission to access the modules list page and check for configure link.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'administer permissions',
      'administer google vision',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the presence of the configuration link.
   */
  public function testConfigurationLink() {
    $this->drupalGet(Url::fromRoute('system.modules_list'));
    $this->assertResponse(200);
    $this->assertText('Google Vision', 'The module is present in the list');
    $this->assertLinkByHref(Url::fromRoute('google_vision.settings')->toString());
  }
}
