<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsContentLanguageOptionsTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsContentLanguageOptionsTest extends BrowserTestBase {
  use TranslatorsContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators_content'];
  /**
   * Default core's langcodes 'und' and 'zxx', which we have to ignore.
   *
   * @var array
   */
  protected static $ignoredLangcodes = [
    Language::LANGCODE_NOT_SPECIFIED,
    Language::LANGCODE_NOT_APPLICABLE,
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Make all required configurations before testing.
    $this->setUpTest();
  }

  /**
   * Test language options on node create form with limited by skills option.
   */
  public function testLanguageOptions() {
    $this->drupalLogin(
      $this->createUser([
        'translators_content create article content',
      ])
    );
    $this->registerTestSkills();

    $this->drupalGet('node/add/article');
    $this->assertResponseCode(200);

    // Find langcode field element.
    $langcode_field = $this->getSession()
      ->getPage()
      ->findField('langcode[0][value]');
    $this->assertNotNull($langcode_field);

    // Get all existing options of the langcode field dropdown.
    $options = $langcode_field->findAll('xpath', '//option');
    $this->assertNotNull($options);

    // Prepare array of options' values.
    $language_options = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $options);

    // Check that we still have "und" and "zxx" langcode options.
    foreach (static::$ignoredLangcodes as $langcode) {
      $this->assertContains($langcode, $language_options);
    }

    // Check that we have all registered skills.
    foreach (static::$registeredSkills as $langcode) {
      $this->assertContains($langcode, $language_options);
      if ($langcode !== 'en') {
        // Additionally check that the direct translation URL is accessible.
        $this->drupalGet($langcode . '/node/add/article');
        $this->assertResponseCode(200);
      }
    }

    // Check that we DO NOT have any unregistered skills options.
    foreach (static::$unregisteredSkills as $langcode) {
      $this->assertNotContains($langcode, $language_options);
      // Additionally check that the direct translation URL is NOT accessible.
      $this->drupalGet($langcode . '/node/add/article');
      $this->assertResponseCode(200, TRUE);
      $this->assertResponseCode(403);
    }
  }

  /**
   * Test source lang options on node create form with limited by skills option.
   */
  public function testSourceLanguageOptions() {
    foreach (['am', 'nl'] as $language) {
      $this->assertEquals(1, ConfigurableLanguage::createFromLangcode($language)->save());
    }
    $this->drupalLogin($this->rootUser);
    // Create testing node.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => $this->randomString(),
    ], 'Save');
    Node::load(1)
      ->addTranslation('fr', ['title' => $this->randomString()])
      ->save();
    Node::load(1)
      ->addTranslation('de', ['title' => $this->randomString()])
      ->save();
    $this->drupalLogout();

    $this->drupalLogin(
      $this->createUser([
        'translators_content create content translations',
        'translate article node',
      ])
    );
    $this->addSkill(static::$registeredSkills);
    $this->addSkill(['am', 'nl']);
    foreach (static::$registeredSkills + ['am', 'nl'] as $skill) {
      $this->assertTrue($this->translatorSkills->hasSkill($skill));
    }

    $this->drupalGet('am/node/1/translations/add/en/am');
    $this->assertResponseCode(200);

    // Find langcode field element.
    $langcode_field = $this->getSession()
      ->getPage()
      ->findField('source_langcode[source]');
    $this->assertNotNull($langcode_field);

    // Get all existing options of the langcode field dropdown.
    $options = $langcode_field->findAll('xpath', '//option');
    $this->assertNotNull($options);

    // Prepare array of options' values.
    $language_options = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $options);

    $this->assertCount(1, $language_options);
    $this->assertContains('en', $language_options);

    $this->drupalLogin(
      $this->createUser([
        'create content translations',
        'translate article node',
      ])
    );
    $this->addSkill(['am', 'nl']);
    $this->addSkill(['fr', 'nl']);
    foreach (['am', 'nl', 'fr'] as $skill) {
      $this->assertTrue($this->translatorSkills->hasSkill($skill));
    }

    $this->drupalGet('am/node/1/translations/add/fr/am');
    $this->assertResponseCode(200);

    // Find langcode field element.
    $langcode_field = $this->getSession()
      ->getPage()
      ->findField('source_langcode[source]');
    $this->assertNotNull($langcode_field);

    $this->assertEquals('fr', $langcode_field->getValue());

    $skills_group = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $langcode_field->findAll('xpath', '//optgroup[@label="Translation skills"]/option'));
    $others_group = array_map(function ($option) {
      return $option->getAttribute('value') ?: $option->getText();
    }, $langcode_field->findAll('xpath', '//optgroup[@label="Others"]/option'));

    $this->assertNotEmpty($skills_group);
    $this->assertCount(1, $skills_group);
    $this->assertContains('fr', $skills_group);

    $this->assertNotEmpty($others_group);
    $this->assertCount(2, $others_group);
    $this->assertContains('en', $others_group);
    $this->assertContains('de', $others_group);
  }

}
