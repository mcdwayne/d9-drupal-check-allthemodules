<?php

/**
 * @file
 * Contains \Drupal\form_protect\Tests\FormProtectTest.
 */

namespace Drupal\form_protect\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Form Protect functionality.
 *
 * @group Spam
 */
class FormProtectTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['form_protect_test'];

  /**
   * Tests if the forms action attribute has changed and
   * Drupal.settings.formProtect contains the correct values.
   */
  public function testFormProtect() {
    // Add the 2 forms to the form protected list.
    $this->config('form_protect.settings')
      ->set('form_ids', ['form_protect_test_form1', 'form_protect_test_form2'])
      ->save();
    $this->drupalGet('form_protect_test');

    $form_protect_settings  = $this->getDrupalSettings()['formProtect'];
    ksort($form_protect_settings);
    $action = Url::fromRoute('form_protect_test.page')->toString();
    $this->assertIdentical($form_protect_settings, [
      'form-protect-test-form1' => $action,
      'form-protect-test-form2' => $action,
    ]);

    $fake_action = Url::fromRoute('form_protect.submit')->toString();
    /** @var \SimpleXMLElement[] $xpath */
    $xpath = $this->xpath("//form[@id='form-protect-test-form1']");
    $attributes = $xpath[0]->attributes();
    $this->assertIdentical((string) $attributes['action'], $fake_action);
    $xpath = $this->xpath("//form[@id='form-protect-test-form2']");
    $attributes = $xpath[0]->attributes();
    $this->assertIdentical((string) $attributes['action'], $fake_action);

    $this->drupalPostForm(NULL, array(), 'Bar1');
    $this->assertText(t('JavaScript is not enabled in your browser. This form requires JavaScript to be enabled.'));

    // The fake submit page should be accessible only by POST method.
    $this->drupalGet('submit/form');
    $this->assertResponse(403);

    // This is not a standard Drupal form submission, it should fail with 403.
    $this->drupalPost('submit/form', 'text/html', ['foo' => 'bar']);
    $this->assertResponse(403);
  }

}
