<?php

namespace Drupal\Tests\obfuscate\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the obfuscation of email.
 *
 * Test is run through Field Formatter, text Filter,
 * Twig Extension for each available method defined via configuration.
 *
 * @group obfuscate
 */
class ObfuscateTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'filter',
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Helper function for testObfuscateFieldFormatter().
   */
  public function testObfuscateFieldFormatter() {
    // @todo implement
    // Add the mail field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_mail',
      'entity_type' => 'node',
      'type' => 'email',
    ])->save();
  }

  /**
   * Helper function for testObfuscateFilter().
   */
  public function testObfuscateFilter() {
    // @todo implement
  }

  /**
   * Helper function for testObfuscateTwigExtension().
   */
  public function testObfuscateTwigExtension() {
    // @todo implement
  }

}
