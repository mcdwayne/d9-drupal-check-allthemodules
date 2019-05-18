<?php

namespace Drupal\Tests\local_translation_content\Functional;

use Drupal\local_translation_content\LocalTranslationContentTestsTrait;
use Drupal\local_translation_content\Plugin\views\filter\TranslationLanguageLimitedToTranslationSkills;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class LocalTranslationLanguageFilterTest.
 *
 * @package Drupal\Tests\local_translation_content\Functional
 *
 * @group local_translation_content
 */
class LocalTranslationLanguageFilterTest extends BrowserTestBase {
  use LocalTranslationContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['local_translation_content', 'local_translation_content_test_views'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpTest();
  }

  /**
   * Test local translation language filter altering.
   */
  public function testLocalTranslationLanguageFilterPluginAltering() {
    $definition = $this->container
      ->get('plugin.manager.views.filter')
      ->getDefinition('language');

    $this->assertNotNull($definition);
    $this->assertArrayHasKey('plugin_type', $definition);
    $this->assertArrayHasKey('id', $definition);
    $this->assertArrayHasKey('class', $definition);
    $this->assertArrayHasKey('provider', $definition);

    $this->assertEquals('local_translation_content', $definition['provider']);
    $this->assertEquals(TranslationLanguageLimitedToTranslationSkills::class, $definition['class']);
    $this->assertEquals('language', $definition['id']);
    $this->assertEquals('filter', $definition['plugin_type']);
  }

  /**
   * Test local translation language filter in view.
   */
  public function testLocalTranslationLanguageFilterInView() {
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

    $this->drupalGet('/test-local-translation-content-filter');
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

    $this->drupalGet('/admin/structure/views/nojs/handler/test_local_translation_content_filter/page_1/filter/langcode');
    $this->drupalPostForm(NULL, [
      'options[limit]'        => 1,
      'options[column][from]' => 1,
      'options[column][to]'   => 1,
    ], 'Apply');
    $this->click('input[value="Save"]');

    $this->drupalGet('/test-local-translation-content-filter');
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
