<?php

namespace Drupal\Tests\admin_toolbar_content_languages\Functional;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\Html;

/**
 * Base class for all administration toolbar - content languages web test cases.
 *
 * @group admin_toolbar_content_languages
 */
class AdminToolbarContentLanguagesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'language',
    'toolbar',
    'admin_toolbar',
    'admin_toolbar_tools',
    'admin_toolbar_content_languages',
    'content_translation',
  ];

  /**
   * A test user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Tests node type links.
   */
  public function testNode() {
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Set Article default language to be the same as the default site language.
    $edit = [];
    $edit['language_configuration[langcode]'] = 'site_default';
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', ['%type' => 'Article']), 'Article content type has been updated.');
    // Clear cache to rebuild menu entries.
    drupal_flush_all_caches();

    // Check that the Article link exists but not the translation link.
    $this->drupalGet('');
    $this->assertLinkTrailByTitle([
      'Content',
      'Add content',
      'Article',
    ]);

    $this->assertNoLinkTrailByTitle([
      'Content',
      'Add content',
      'Article',
      'Article (English)',
    ]);

    // Set Article default language to be the same as the current page language.
    $edit = ['language_configuration[langcode]' => 'current_interface'];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', ['%type' => 'Article']), 'Article content type has been updated.');
    // Clear cache to rebuild menu entries.
    drupal_flush_all_caches();

    // Check that the Article links get created when we change the default
    // language to the current page language.
    $this->drupalGet('');
    $this->assertLinkTrailByTitle([
      'Content',
      'Add content',
      'Article',
      'Article (English)',
    ]);

    $this->assertLinkTrailByTitle([
      'Content',
      'Add content',
      'Article',
      'Article (French)',
    ]);

    // Re-check paths, without using menu trails.
    $links = [
      'node/add/article' => 'Article',
      'fr/node/add/article' => 'Article (French)',
      'node/add/article' => 'Article (English)',
    ];
    foreach ($links as $path => $title) {
      $this->assertElementByXPath('//div[@id="toolbar-administration"]//a[contains(@href, :path) and text()=:title]', [
        ':path' => $path,
        ':title' => $title,
      ], "Add content » $title link found.");
    }
  }

  /**
   * Asserts that links appear in the menu in a specified trail.
   *
   * @param array $trail
   *   A list of menu link titles to assert in the menu.
   */
  protected function assertLinkTrailByTitle(array $trail) {
    $xpath = [];
    $args = [];
    $message = '';
    foreach ($trail as $i => $title) {
      $xpath[] = '/li/a[text()=:title' . $i . ']';
      $args[':title' . $i] = $title;
      $message .= ($i ? ' » ' : '') . HTML::escape($title);
    }
    $xpath = '//div[@id="toolbar-administration"]//div[@class="toolbar-menu-administration"]/ul' . implode('/parent::li/ul', $xpath);
    $this->assertElementByXPath($xpath, $args, $message . ' link found.');
  }

  /**
   * Check that an element exists in HTML markup.
   *
   * @param string $xpath
   *   An XPath expression.
   * @param array $arguments
   *   (optional) An associative array of XPath replacement tokens to pass to
   *   DrupalWebTestCase::buildXPathQuery().
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertElementByXpath($xpath, array $arguments = [], $message = '', $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    return $this->assertTrue(!empty($elements[0]), $message, $group);
  }

  /**
   * Asserts that no link appears in the menu for a specified trail.
   *
   * @param array $trail
   *   A list of menu link titles to assert in the menu.
   */
  protected function assertNoLinkTrailByTitle(array $trail) {
    $xpath = [];
    $args = [];
    $message = '';
    foreach ($trail as $i => $title) {
      $xpath[] = '/li/a[text()=:title' . $i . ']';
      $args[':title' . $i] = $title;
      $message .= ($i ? ' » ' : '') . Html::escape($title);
    }
    $xpath = '//div[@id="toolbar-administration"]//div[@class="toolbar-menu-administration"]/ul' . implode('/parent::li/ul', $xpath);
    $this->assertNoElementByXPath($xpath, $args, $message . ' link not found.');
  }

  /**
   * Check that an element does not exist in HTML markup.
   *
   * @param string $xpath
   *   An XPath expression.
   * @param array $arguments
   *   (optional) An associative array of XPath replacement tokens to pass to
   *   DrupalWebTestCase::buildXPathQuery().
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoElementByXpath($xpath, array $arguments = [], $message = '', $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    return $this->assertTrue(empty($elements), $message, $group);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content overview',
      'access toolbar',
      'administer content types',
      'administer languages',
      'administer nodes',
      'administer site configuration',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->addLanguage('fr');
  }

  /**
   * Installs the specified language, or enables it if it is already installed.
   *
   * @param string $language_code
   *   The language code to check.
   */
  private function addLanguage($language_code) {
    // Check to make sure that language has not already been installed.
    $this->drupalGet('admin/config/regional/language');

    if (strpos($this->getRawContent(), 'value="' . $language_code . '"') === FALSE) {
      // Doesn't have language installed so add it.
      $edit = [];
      $edit['predefined_langcode'] = $language_code;
      $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

      // Check to make sure that language has been installed.
      $this->drupalGet('admin/config/regional/language');
      $this->assertRaw('value="' . $language_code . '"');
    }
    else {
      // It's installed. No need to do anything.
      $this->assertTrue(TRUE, 'Language [' . $language_code . '] already installed and enabled.');
    }

    $this->refreshVariables();
  }

}
