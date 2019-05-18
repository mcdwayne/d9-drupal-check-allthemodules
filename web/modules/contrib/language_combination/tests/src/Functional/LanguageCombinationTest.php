<?php

namespace Drupal\Tests\language_combination\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Class LanguageCombinationTest.
 *
 * Tests for Language Combination module.
 *
 * @group LanguageCombinationTest
 */
class LanguageCombinationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'language',
    'language_combination',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'edit any article content',
      'administer content types',
    ]);
    $this->drupalLogin($user);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    FieldStorageConfig::create([
      'field_name' => 'language_combination',
      'entity_type' => 'node',
      'type' => 'language_combination',
    ])->save();

    FieldConfig::create([
      'field_name' => 'language_combination',
      'label' => 'Language Combination',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent('language_combination')
      ->save();
    $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.article.default')
      ->setComponent('language_combination')
      ->save();
  }

  /**
   * Display creation form.
   */
  public function testLanguageCombinationFormFieldDisplay() {
    $this->drupalGet('node/add/article');
    $session = $this->assertSession();
    $session->pageTextContains('Language Combination');
    $session->fieldExists("language_combination[0][language_source]");
    $session->fieldExists("language_combination[0][language_target]");
  }

  /**
   * Test basic validation of language combinations.
   */
  public function testLanguageCombinationFormFieldValidation() {
    $this->drupalGet('node/add/article');
    $edit = [
      'title[0][value]'                           => $this->randomString(),
      'language_combination[0][language_source]' => 'fr',
      'language_combination[0][language_target]' => 'fr',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->pageTextContains("The 'from' and 'to' language fields can't have the same value.");
  }

  /**
   * Test basic entry of language combinations.
   */
  public function testLanguageCombinationFormFieldSubmission() {
    $this->drupalGet('node/add/article');
    $edit = [
      'title[0][value]'                           => $this->randomString(),
      'language_combination[0][language_source]' => 'fr',
      'language_combination[0][language_target]' => 'de',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->pageTextContains('French to German');
  }

}
