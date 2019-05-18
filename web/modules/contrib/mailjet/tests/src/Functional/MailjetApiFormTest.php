<?php

namespace Drupal\Tests\mailjet\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group mailjet
 */
class MailjetApiFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mailjet'];

  /**
   * A simple user with 'access content' permission
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['access content']);
  }


  public function testApiForm() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/system/mailjet/api');
    $this->assertResponse(200);

    $config = $this->config('mailjet.settings');
    $this->assertFieldByName('mailjet_username', $config->get('mailjet.mailjet_username'), 'The field was found with the correct value.');
    $this->assertFieldByName('mailjet_password', $config->get('mailjet.mailjet_password'), 'The field was found with the correct value.');


    $this->drupalPostForm(NULL, [
      'mailjet_username' => $config->get('mailjet_username'),
      'mailjet_username' => $config->get('mailjet_password'),
    ], t('Save configuration'));
    $this->assertText('The configuration options have been saved.', 'The form was saved correctly.');


  }

}

