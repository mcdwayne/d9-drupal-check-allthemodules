<?php

namespace Drupal\Tests\partial_multi\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Partially Multilingual module.
 *
 * @group partial_multi
 */
class PartialMultiTest extends BrowserTestBase {

  /**
   * Untranslated node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $untranslatedNode;

  /**
   * Translated node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $translatedNode;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'partial_multi',
    'language',
    'content_translation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a user with permission to administer the module, as well as
    // languages, and create and see content.
    $adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer languages',
      'bypass node access',
      'administer content types',
      'administer content translation',
      'translate any entity',
    ]);
    $this->drupalLogin($adminUser);

    // Add Spanish language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit,
      t('Add language'));
    $this->assertText('Spanish', 'Language added successfully.');

    // Create Article content type and make it translatable.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    \Drupal::service('router.builder')->rebuild();
    $this->rebuildContainer();

    // Create an article and translate it.
    $this->translatedNode = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $this->translatedNode->id() . '/translations/add/en/es');
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(32),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save (this translation)');

    // Create an article that is not translated.
    $this->untranslatedNode = $this->drupalCreateNode(['type' => 'article']);
  }

  /**
   * Tests redirects in the Partially Multilingual module.
   */
  public function testRedirects() {
    $this->verifyRedirects();

    // Set redirect to 302 and verify that it still redirects.
    $edit = [
      'redirect_code' => 302,
    ];
    $this->drupalPostForm('admin/config/regional/partial-multi', $edit, 'Save configuration');
    $this->verifyRedirects();
  }

  /**
   * Performs the actual redirect testing on the translated/untranslated nodes.
   */
  public function verifyRedirects() {
    // Translated node should be visible in both languages without redirect.
    // Untranslated node should redirect to English if requested in Spanish.
    $urls = [
      [
        'node/' . $this->translatedNode->id(),
        'node/' . $this->translatedNode->id(),
      ],
      [
        'es/node/' . $this->translatedNode->id(),
        'es/node/' . $this->translatedNode->id(),
      ],
      [
        'node/' . $this->untranslatedNode->id(),
        'node/' . $this->untranslatedNode->id(),
      ],
      [
        'es/node/' . $this->untranslatedNode->id(),
        'node/' . $this->untranslatedNode->id(),
      ],
    ];

    foreach ($urls as $url_item) {
      $this->drupalGet($url_item[0]);
      $this->assertUrl($url_item[1]);
    }
  }

}
