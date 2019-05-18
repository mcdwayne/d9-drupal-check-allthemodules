<?php

namespace Drupal\Tests\permission_ui\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides setup and helper methods for permission ui tests.
 */
abstract class PermissionUiTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'permission_ui',
  ];

  /**
   * An administrative user with permission to configure Permission UI settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal user with new permission to operate an entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an article content type only if it does not yet exist, so that
    // child classes may specify the standard profile.
    $types = NodeType::loadMultiple();
    if (empty($types['article'])) {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => t('Article'),
      ]);
    }

    // Create test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer users',
      'administer permissions',
      'administer permission ui',
    ]);
  }

  /**
   * Asserts local action in the page output.
   *
   * @param string $title
   *   String title.
   * @param string $url
   *   String URL.
   */
  protected function assertLocalAction($title, $url) {
    $pattern = '@<a [^>]*class="[^"]*button-action[^"]*"[^>]*>' . preg_quote($title, '@') . '</@';
    $this->assertSession()->responseMatches($pattern);
    $this->assertSession()->linkByHrefExists($url->toString());
  }

}
