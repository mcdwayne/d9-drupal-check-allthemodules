<?php

namespace Drupal\Tests\translation_form\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Url;

/**
 * Class ConfigsTest.
 *
 * @package Drupal\Tests\translation_form\Functional
 *
 * @group translation_form
 */
class ChangeSourceTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['translation_form'];

  /**
   * The added languages.
   *
   * @var string[]
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $langcodes = ['de', 'fr'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
      'administer content types',
    ]);
    $this->drupalLogin($user);

    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    // Enable allow to change source language on edit page.
    $edit = [
      'allow_to_change_source_language' => TRUE,
    ];
    $this->drupalPostForm(Url::fromRoute('translation_form.settings_form'), $edit, t('Save configuration'));
  }

  /**
   * Tests check if user can see and save source language on edit entity form.
   */
  public function testSourceLanguageFormElementsExistence() {
    // Create a node in English.
    $node = $this->drupalCreateNode(['type' => 'article', 'langcode' => 'en']);

    // Create a translation in German.
    $this->drupalGet($node->urlInfo('drupal:content-translation-overview'));
    $this->clickLink('Add');
    $this->drupalPostForm(NULL, [], t('Save (this translation)'));

    // Create a translation in French.
    $this->drupalGet($node->urlInfo('drupal:content-translation-overview'));
    $this->clickLink('Add');
    $this->drupalPostForm(NULL, [], t('Save (this translation)'));

    // Go to edit page for French language and change source language.
    $this->drupalGet($node->urlInfo('drupal:content-translation-overview'));
    $this->clickLink('Edit', 1);
    $edit = [
      'source_langcode[source]' => 'de',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));

    // Check source language for tranlsate for node.
    $this->drupalGet($node->urlInfo('drupal:content-translation-overview'));
    $elements = $this->xpath('//tbody/tr[2]/td[3]/text()');
    $this->assertEquals($elements[0]->getText(), t('German'), format_string('Source language is correct for %language translation.', ['%language' => 'French']));
  }

}
