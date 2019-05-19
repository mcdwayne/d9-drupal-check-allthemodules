<?php

namespace Drupal\Tests\tome_netlify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the deploy form loads correctly.
 *
 * @group tome_netlify
 */
class DeployFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'tome_netlify',
    'tome_static',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['use tome static']));
  }

  /**
   * Tests that the deploy form loads correctly.
   */
  public function testDeployForm() {
    $directory = $this->siteDirectory . '/files/tome/static';
    $settings['settings']['tome_static_directory'] = (object) [
      'value' => $directory,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config/tome/netlify/send');
    $assert_session->pageTextContains('Tome Netlify has not been configured.');
    $assert_session->pageTextContains('No static build available for deploy.');

    $this->drupalGet('/admin/config/services/tome_netlify/settings');
    $this->submitForm([
      'access_token' => '123',
      'site_id' => '123',
    ], 'Save');

    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

    $this->drupalGet('/admin/config/tome/netlify/send');
    $assert_session->elementExists('css', 'input[type="submit"][value="Deploy"]');
  }

}
