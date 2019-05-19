<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\translators_content\Plugin\views\filter\TranslationLanguageLimitedToTranslationSkills;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsLanguageFilterTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsLanguageFilterTest extends BrowserTestBase {
  use TranslatorsContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators_content', 'translators_content_test_views'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpTest();
  }

  /**
   * Test Translators language filter altering.
   */
  public function testTranslatorsLanguageFilterPluginAltering() {
    $definition = $this->container
      ->get('plugin.manager.views.filter')
      ->getDefinition('language');

    $this->assertNotNull($definition);
    $this->assertArrayHasKey('plugin_type', $definition);
    $this->assertArrayHasKey('id', $definition);
    $this->assertArrayHasKey('class', $definition);
    $this->assertArrayHasKey('provider', $definition);

    $this->assertEquals('translators_content', $definition['provider']);
    $this->assertEquals(TranslationLanguageLimitedToTranslationSkills::class, $definition['class']);
    $this->assertEquals('language', $definition['id']);
    $this->assertEquals('filter', $definition['plugin_type']);
  }

  /**
   * Test Translators language filter in view.
   */
  public function testTranslatorsLanguageFilterInView() {
    $this->drupalLogin($this->rootUser);
    $this->registerTestSkills();
    for ($i = 1; $i <= 10; $i++) {
      Node::create([
        'type' => 'article',
        'title' => $this->randomString(),
        'langcode' => static::$registeredSkills[0],
      ])
        ->addTranslation(static::$registeredSkills[1], ['title' => $this->randomString()])
        ->save();
    }

    $this->drupalGet('/test-translators-content-filter');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->statusCodeNotEquals(404);

    // Find langcode field element.
    $langcode_field = $this->getSession()
      ->getPage()
      ->findField('langcode');
    $this->assertNotNull($langcode_field);

    // Get all existing options of the langcode filter dropdown.
    $options = $langcode_field->findAll('xpath', '//option');
    $this->assertNotNull($options);

    // Prepare array of options' values.
    $language_options = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $options);

    $this->assertCount(9, $language_options);
    $this->assertContains('en', $language_options);
    $this->assertContains('fr', $language_options);
    $this->assertContains('de', $language_options);
    $this->assertContains('sq', $language_options);

    $this->drupalGet('/admin/structure/views/nojs/handler/test_translators_content_filter/page_1/filter/langcode');
    $this->drupalPostForm(NULL, [
      'options[limit]'          => 1,
      'options[column][source]' => 1,
      'options[column][target]' => 1,
    ], 'Apply');
    $this->click('input[value="Save"]');

    $this->drupalGet('/test-translators-content-filter');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->statusCodeNotEquals(404);

    // Find langcode field element.
    $langcode_field = $this->getSession()
      ->getPage()
      ->findField('langcode');
    $this->assertNotNull($langcode_field);

    // Get all existing options of the langcode filter dropdown.
    $options = $langcode_field->findAll('xpath', '//option');
    $this->assertNotNull($options);

    // Prepare array of options' values.
    $language_options = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $options);

    $this->assertCount(3, $language_options);
    $this->assertContains('All', $language_options);
    $this->assertContains('en', $language_options);
    $this->assertContains('fr', $language_options);
    $this->assertNotContains('de', $language_options);
    $this->assertNotContains('sq', $language_options);

  }

}
