<?php

namespace Drupal\Tests\google_vision\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests whether API key is set at the admin configuration page, and whether the
 * API Key is stored successfully or not.
 *
 * @group google_vision
 */
class ApiKeyConfigurationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file_entity', 'google_vision'];

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser(array('administer google vision','administer site configuration')
    );
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test to implement a runtime requirement checking if API key is not set.
   */
  public function testKeyConfiguration() {
    $page = $this->getSession()->getPage();
    $settings = Url::fromRoute('google_vision.settings')->toString();
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('api_key', '', 'The API key is not set');
    $this->drupalGet(Url::fromRoute('system.status'));
    $this->assertResponse(200);
    $this->assertText('Google Vision API key is not set and it is required for some functionalities to work.', 'The content exists on the report page');
    $this->assertLink('Google Vision module settings page', 0, 'Link to the api key configuration page found');
    // @TODO replace this later by assertLinkByHref function when added to AssertLegacyTrait
    $this->assertNotEmpty($page->find('xpath', "//a[contains(@href, $settings)]"), 'The system status page has google vision settings page link.');
  }

  /**
   * Test to verify that it is stored successfully.
   */
  public function testKeyStorage() {
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('api_key', '', 'The key is not set currently');
    $api_key = $this->randomString(40);
    $edit = array('api_key' => $api_key);
    $this->drupalPostForm(Url::fromRoute('google_vision.settings'), $edit, t('Save configuration'));
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('api_key', $api_key, 'The key has been saved');
  }
}
