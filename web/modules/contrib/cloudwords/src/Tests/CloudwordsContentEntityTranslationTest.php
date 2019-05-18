<?php

namespace Drupal\cloudwords\Tests;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests translating a node.
 *
 * @group cloudwords
 */
class CloudwordsContentEntityTranslationTest extends CloudwordsTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'image', 'views'];

  /**
   * @var NodeInterface
   */
  protected $node;

  protected function setUp() {
    parent::setUp();
   // @todo need to set up content and configs
  }

  /**
   * Tests that a node can be translated.
   */
  public function testContentEntityTranslation() {
    // Login as admin.

    // @todo run through to project export

  }
}