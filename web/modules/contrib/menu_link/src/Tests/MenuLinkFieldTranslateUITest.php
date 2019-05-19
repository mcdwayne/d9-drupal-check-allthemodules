<?php

namespace Drupal\menu_link\Tests;

use Drupal\node\Tests\NodeTranslationUITest;

/**
 * Tests the translation support for menu field based menu links.
 *
 * @group menu_link
 */
class MenuLinkFieldTranslateUITest extends NodeTranslationUITest {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'content_translation',
    'node',
    'menu_link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return ['administer menu', 'administer nodes', "create $this->bundle content"];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer menu', 'administer nodes', "edit any $this->bundle content"]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), ['administer menu', 'access administration pages', 'administer content types', 'administer node fields', 'access content overview', 'bypass node access', 'administer languages', 'administer themes', 'view the administration theme']);
  }

}
