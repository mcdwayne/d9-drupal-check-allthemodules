<?php

namespace Drupal\Tests\node_edit_redirect\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that the node edit redirect works.
 *
 * @group node_edit_redirect
 */
class NodeEditRedirectTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node_edit_redirect',
    'locale',
    'content_translation',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Add the French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Rebuild the container so that the newly created language is picked up.
    $this->rebuildContainer();
    // Set prefixes to en and fr.
    $this->drupalPostForm('admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    // Set up URL method.
    $this->drupalPostForm('admin/config/regional/language/detection', [
      'language_interface[enabled][language-url]' => 1,
    ], 'Save settings');
    // Turn on content translation for pages.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 1], 'Save content type');
  }

  /**
   * Test that the redirect works as intended.
   */
  public function testRedirect() {
    $node = $this->drupalCreateNode(['langcode' => 'fr']);

    $languages = $this->container->get('language_manager')->getLanguages();
    // Visit the English page for a French node.
    $this->drupalGet('node/' . $node->id() . '/edit', [
      'absolute' => TRUE,
      'language' => $languages['en'],
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $current_url = $this->getUrl();

    // Assert that we redirected to the French edit page.
    $this->assertEquals($current_url, Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], [
      'absolute' => TRUE,
      'language' => $languages['fr'],
    ])->toString());
    $this->assertNotEquals($current_url, Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], [
      'absolute' => TRUE,
      'language' => $languages['en'],
    ])->toString());

    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall(['node_edit_redirect']);
    // Visit the English page for a French node.
    $this->drupalGet('node/' . $node->id() . '/edit', [
      'absolute' => TRUE,
      'language' => $languages['en'],
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $current_url = $this->getUrl();
    // Assert that we did not redirect to the French edit page, now that the
    // module is uninstalled.
    $this->assertEquals($current_url, Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], [
      'absolute' => TRUE,
      'language' => $languages['en'],
    ])->toString());
    $this->assertNotEquals($current_url, Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], [
      'absolute' => TRUE,
      'language' => $languages['fr'],
    ])->toString());

  }

}
