<?php

namespace Drupal\Tests\acquia_contenthub\Functional;

use Drupal\acquia_contenthub_test\MockDataProvider;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Content Hub settings form.
 *
 * @group acquia_contenthub
 */
class ContentHubSettingsFormTest extends BrowserTestBase {

  /**
   * User that has administer permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $authorizedUser;

  /**
   * Anonymous user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * Path to content hub settings form.
   */
  const CH_SETTINGS_FORM_PATH = '/admin/config/services/acquia-contenthub';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'acquia_contenthub',
    'acquia_contenthub_test',
    'acquia_contenthub_server_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->authorizedUser = $this->drupalCreateUser([
      'administer acquia content hub',
    ]);

    $this->unauthorizedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->authorizedUser);
  }

  /**
   * Tests permissions of different users.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsPagePermissions() {
    $session = $this->assertSession();

    $this->drupalGet(self::CH_SETTINGS_FORM_PATH);
    $session->pageTextContains('Acquia Content Hub Settings');
    $session->statusCodeEquals(200);

    $this->drupalLogout();
    $this->drupalLogin($this->unauthorizedUser);

    $this->drupalGet(self::CH_SETTINGS_FORM_PATH);
    $session->pageTextContains('Access denied');
    $session->statusCodeEquals(403);
  }

  /**
   * Tests whether fields rendered properly.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsFormRenderedProperly() {
    $session = $this->assertSession();

    $this->drupalGet(self::CH_SETTINGS_FORM_PATH);
    $session->fieldExists('Acquia Content Hub Hostname');
    $session->fieldExists('API Key');
    $session->fieldExists('Secret Key');
    $session->fieldExists('Client Name');
    $session->fieldExists('Publicly Accessible URL');
    $session->pageTextContains('Site\'s Origin UUID');
    $session->buttonExists('Register Site');
    $session->buttonNotExists('Update Public URL');
    $session->buttonNotExists('Unregister Site');
    // Test prefilled webhook field.
    $session->fieldValueEquals('webhook', \Drupal::request()->getSchemeAndHttpHost());
  }

  /**
   * Tests empty form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsPageNoData() {
    $session = $this->assertSession();

    $settings = [
      'webhook' => 'httpp://invalid-url.com',
    ];

    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains('Acquia Content Hub Hostname field is required.');
    $session->pageTextContains('API Key field is required.');
    $session->pageTextContains('Secret Key field is required.');
    $session->pageTextContains('Client Name field is required.');
    $session->pageTextContains('Please type a publicly accessible url.');
  }

  /**
   * Tests the successful registration.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsPageWithValidData() {
    $session = $this->assertSession();

    $settings = [
      'hostname' => MockDataProvider::VALID_HOSTNAME,
      'api_key' => MockDataProvider::VALID_API_KEY,
      'secret_key' => MockDataProvider::VALID_SECRET,
      'client_name' => MockDataProvider::VALID_CLIENT_NAME,
      'webhook' => MockDataProvider::VALID_WEBHOOK_URL,
    ];

    // Successful attempt to register client.
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains('Site successfully connected to Content Hub. To change connection settings, unregister the site first.');
    $session->statusCodeEquals(200);

    $session->buttonNotExists('Register Site');
    $session->buttonExists('Update Public URL');
    $session->buttonExists('Unregister Site');

    // Successful attempt to unregister client.
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, [], 'Unregister Site');
    $session->pageTextContains(sprintf('Successfully disconnected site %s from Content Hub.', $settings['client_name']));

    $session->buttonExists('Register Site');
    $session->buttonNotExists('Update Public URL');
    $session->buttonNotExists('Unregister Site');
  }

  /**
   * Tests successful registration with the exception of the webhook.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsPageWithPartiallyValidData() {
    $session = $this->assertSession();

    $settings = [
      'hostname' => MockDataProvider::VALID_HOSTNAME,
      'api_key' => MockDataProvider::VALID_API_KEY,
      'secret_key' => MockDataProvider::VALID_SECRET,
      'client_name' => MockDataProvider::VALID_CLIENT_NAME,
      'webhook' => 'http://invalid-url.com',
    ];

    // Successful attempt to register client, but webhook url is unreachable.
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains('Site successfully connected to Content Hub. To change connection settings, unregister the site first.');
    $session->statusCodeEquals(200);

    $session->buttonNotExists('Register Site');
    $session->buttonExists('Update Public URL');
    $session->buttonExists('Unregister Site');

    // Failed attempt to update url.
    $settings['webhook'] = MockDataProvider::ALREADY_REGISTERED_WEBHOOK;
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Update Public URL');
    $session->pageTextContains(sprintf('The webhook url "%s/acquia-contenthub/webhook" is already being used. Please insert another one, or unregister the existing one first.', $settings['webhook']));

    // Successful attempt to update url.
    $settings['webhook'] = MockDataProvider::VALID_WEBHOOK_URL;
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Update Public URL');
    $session->pageTextContains('Site successfully connected to Content Hub. To change connection settings, unregister the site first.');
    $session->pageTextContains('Successfully Updated Public URL.');
  }

  /**
   * Tests different cases of invalid data provided through the form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testContentHubSettingsPageWithInvalidData() {
    $session = $this->assertSession();

    $settings = [
      'hostname' => 'https://invalid-url.com',
      'api_key' => 'invalid',
      'secret_key' => 'invalid',
      'client_name' => 'test',
      'webhook' => MockDataProvider::VALID_WEBHOOK_URL,
    ];

    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains(sprintf('Could not get authorization from Content Hub to register client %s. Are your credentials inserted correctly?', $settings['client_name']));
    $session->pageTextContains('There is a problem connecting to Acquia Content Hub. Please ensure that your hostname and credentials are correct.');

    $settings['hostname'] = MockDataProvider::VALID_HOSTNAME;
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains(sprintf('[4001] Not Found: Customer Key %s could not be found.', $settings['api_key']));

    $settings['api_key'] = MockDataProvider::VALID_API_KEY;
    $this->drupalPostForm(self::CH_SETTINGS_FORM_PATH, $settings, 'Register Site');
    $session->pageTextContains('[4001] Signature for the message does not match expected signature for API key.');
  }

}
