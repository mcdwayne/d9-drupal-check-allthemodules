<?php

namespace Drupal\Tests\sendwithus\Functional;

use Drupal\key\Entity\Key;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests SettingsForm.
 *
 * @coversDefaultClass \Drupal\sendwithus\Form\SettingsForm
 * @group sendwithus
 */
class AdminSettingsTest extends BrowserTestBase {

  protected const PATH = '/admin/config/services/sendwithus';
  protected const TEST_PATH = '/admin/config/services/sendwithus/test';

  public static $modules = ['sendwithus', 'key'];

  /**
   * @covers ::__construct
   * @covers ::create
   * @covers ::getFormId
   * @covers ::buildForm
   * @covers ::submitForm
   * @covers ::getEditableConfigNames
   * @covers ::getModulesList
   */
  public function testForm() {
    // Make sure we don't have access to configuration without a
    // proper permission.
    foreach ([static::PATH, static::TEST_PATH] as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }

    $account = $this->createUser(['administer sendwithus']);
    $this->drupalLogin($account);

    foreach (['sendwithus', 'sendwithus2'] as $i => $value) {
      $key = Key::create([
        'id' => $value,
        'label' => $value,
      ]);
      $key->setKeyValue(123 + $i);
      $key->save();
    }

    $this->drupalGet(static::PATH);
    $this->assertSession()->pageTextContains('No templates set.');

    $this->submitForm(['api_key' => 'sendwithus', 'set_default' => 1], 'Submit');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $mailsystem = $this->config('system.mail')->get('interface.default');
    $this->assertEquals($mailsystem, 'sendwithus_mail');

    // Make sure we can add new templates.
    $this->submitForm([
      'templates[template]' => 'template_id',
      'templates[module]' => 'user',
      'templates[key]' => 'password_reset',
    ], 'Submit');

    $this->assertSession()->fieldValueEquals('templates[templates][0][template]', 'template_id');
    $this->assertSession()->fieldValueEquals('templates[templates][0][module]', 'user');
    $this->assertSession()->fieldValueEquals('templates[templates][0][key]', 'password_reset');

    // Make sure we can edit templates.
    $this->submitForm([
      'templates[templates][0][template]' => 'template_id1',
      'templates[templates][0][key]' => 'password_reset1',
    ], 'Submit');

    $this->assertSession()->fieldValueEquals('templates[templates][0][template]', 'template_id1');
    $this->assertSession()->fieldValueEquals('templates[templates][0][key]', 'password_reset1');

    // Make sure we can delete templates.
    $this->submitForm([
      'templates[templates][0][remove]' => '1',
    ], 'Submit');

    $this->assertSession()->pageTextContains('No templates set.');
  }

}
