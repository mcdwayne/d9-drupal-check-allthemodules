<?php

namespace Drupal\Tests\translation_form\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Class ConfigsTest.
 *
 * @package Drupal\Tests\translation_form\Functional
 *
 * @group translation_form
 */
class ConfigsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['translation_form'];

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
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests configuration form existence.
   */
  public function testConfigFormExistence() {
    $this->drupalGet(Url::fromRoute('translation_form.settings_form'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests configuration form elements existence.
   */
  public function testConfigFormElementsExistence() {
    $this->drupalGet(Url::fromRoute('translation_form.settings_form'));
    static $config_elements = [
      'always_display_original_language_translation',
      'hide_languages_without_translation',
      'allow_to_change_source_language',
    ];
    foreach ($config_elements as $config_element_name) {
      $this->assertSession()
        ->elementExists(
          'css',
          "[name=\"$config_element_name\"]"
        );
    }
  }

}
