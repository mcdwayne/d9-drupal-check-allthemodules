<?php

namespace Drupal\Tests\commerce_abandoned_carts\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuring the module.
 *
 * @group commerce_abandoned_carts
 */
class AdminFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_abandoned_carts'];

  /**
   * Tests that an user without the right permissions cannot access the form.
   */
  public function testAccessDenied() {
    $account = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/commerce/config/abandoned_carts');
    $this->assertResponse(403);
  }

  /**
   * Tests filling the form.
   */
  public function testFillForm() {
    $account = $this->drupalCreateUser(['administer commerce abandonded carts']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/commerce/config/abandoned_carts');
    $this->assertResponse(200);

    // Change all settings.
    $edit = [
      'timeout' => 120,
      'history_limit' => 10080,
      'batch_limit' => 10,
      'from_email' => 'fromme@example.com',
      'from_name' => 'From me',
      'subject' => 'You did not complete your order',
      'customer_service_phone_number' => '123',
      'bcc_active' => 1,
      'bcc_email' => 'bcc@example.com',
      'testmode' => 1,
      'testmode_email' => 'test@example.com',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check saved settings.
    $expected = [
      'timeout' => 120,
      'history_limit' => 10080,
      'batch_limit' => 10,
      'from_email' => 'fromme@example.com',
      'from_name' => 'From me',
      'subject' => 'You did not complete your order',
      'customer_service_phone_number' => '123',
      'bcc_active' => 1,
      'bcc_email' => 'bcc@example.com',
      'testmode' => 1,
      'testmode_email' => 'test@example.com',
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $this->config('commerce_abandoned_carts.settings')->get($key));
    }
  }

  /**
   * Tests that Bcc email is only required when setting 'bcc_active' to active.
   */
  public function testBccEmailRequiredWhenActivatingBcc() {
    // Disable test mode.
    $this->config('commerce_abandoned_carts.settings')
      ->set('testmode', FALSE)
      ->save();

    $account = $this->drupalCreateUser(['administer commerce abandonded carts']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/commerce/config/abandoned_carts');

    // Try to only enable bcc without putting in a mail address.
    $this->submitForm(['bcc_active' => 1], 'Save configuration');
    $this->assertSession()->pageTextContains('BCC email address field is required when Send BCC is enabled.');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Assert that the config hasn't changed.
    $this->assertEquals(0, $this->config('commerce_abandoned_carts.settings')->get('bcc_active'));

    // Now try to set bcc again.
    $this->submitForm([
      'bcc_active' => 1,
      'bcc_email' => 'bcc@example.com',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Assert that the config has changed now.
    $this->assertEquals(1, $this->config('commerce_abandoned_carts.settings')->get('bcc_active'));
  }

  /**
   * Tests that test email is only required when setting 'testmode' to active.
   */
  public function testTestEmailRequiredWhenActivatingTestMode() {
    $account = $this->drupalCreateUser(['administer commerce abandonded carts']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/commerce/config/abandoned_carts');

    // Try to only enable testmode without putting in a mail address.
    $this->submitForm(['testmode' => 1], 'Save configuration');
    $this->assertSession()->pageTextContains('Test mode email address field is required when Test mode is enabled.');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Now try to set testmode again.
    $this->submitForm([
      'testmode' => 1,
      'testmode_email' => 'test@example.com',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Assert that the config has changed now.
    $this->assertEquals(1, $this->config('commerce_abandoned_carts.settings')->get('testmode'));
  }

}
