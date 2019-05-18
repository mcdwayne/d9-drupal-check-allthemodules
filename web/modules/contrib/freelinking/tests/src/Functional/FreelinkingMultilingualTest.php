<?php

namespace Drupal\Tests\freelinking\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that multilingual capabilities of Freelinking.
 *
 * @group freelinking
 */
class FreelinkingMultilingualTest extends FreelinkingBrowserTestBase {

  public static $modules = [
    'node',
    'user',
    'file',
    'filter',
    'search',
    'language',
    'content_translation',
    'freelinking',
  ];

  /**
   * User with administrative capability.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp() {
    parent::setUp();

    $this->updateFilterSettings();

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a user to administer languages.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer content types',
      'administer filters',
      'administer languages',
      'administer content translation',
      'access user profiles',
      'create page content',
      'create content translations',
      'edit own page content',
      'update content translations',
      'translate any entity',
    ]);
    $this->drupalLogin($this->adminUser);

    // Enable URL and User language detection and selection.
    $edit = [
      'language_interface[enabled][language-url]' => '1',
      'language_interface[enabled][language-user]' => '1',
    ];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Enable multilingual support for "Basic page" content type.
    $edit = ['language_configuration[language_alterable]' => TRUE];
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertSession()
      ->pageTextContains('The content type Basic page has been updated.');

    // Create a node translation.
    $edit = [
      'title[0][value]' => 'Primera página',
      'body[0][value]' => 'Contenido traducido.',
    ];
    $this->drupalPostForm('node/1/translations/add/en/es', $edit, t('Save'));
  }

  /**
   * Asserts that node title is translated into user's preferred language.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFreelinking() {
    $edit = [];
    $edit['title[0][value]'] = t('Testing all freelinking plugins');
    $edit['body[0][value]'] = <<< EOF
      <ul>
        <li>Default plugin (nodetitle): [[First page]]</li>
        <li>Nodetitle: [[nodetitle:First page]]</li>
        <li>Nid: [[nid:1]]</li>
      </ul>
EOF;
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $this->assertSession()
      ->linkExists('First page', 0, 'Generate default plugin (nodetitle) freelink.');
    $this->assertSession()
      ->linkExists('First page', 0, 'Generate Nodetitle freelink.');
    $this->assertSession()
      ->linkExists('First page', 0, 'Generate Nid freelink.');

    $this->drupalLogout();

    // Create an user with preferred language of Spanish.
    $esUser = $this->createUser(['access content']);
    $esUser->set('preferred_langcode', 'es');
    $esUser->save();
    $this->assertEquals('es', $esUser->getPreferredLangcode());

    $this->drupalLogin($esUser);

    $this->drupalGet('/node/3');
    $this->assertSession()
      ->linkExists('Primera página', 0, 'Generate default plugin (nodetitle) freelink in Spanish.');
    $this->assertSession()
      ->linkExists('Primera página', 0, 'Generate Nodetitle freelink in Spanish.');
    $this->assertSession()
      ->linkExists('Primera página', 0, 'Generate Nid freelink in Spanish.');
  }

}
