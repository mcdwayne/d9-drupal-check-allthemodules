<?php

namespace Drupal\local_translation_content;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Trait LocalTranslationContentTestsTrait.
 *
 * @package Drupal\local_translation_content
 */
trait LocalTranslationContentTestsTrait {

  /**
   * Local translation skills service.
   *
   * @var \Drupal\local_translation\Services\LocalTranslationUserSkills
   */
  protected $skills;
  /**
   * User registered skills.
   *
   * @var array
   */
  protected static $registeredSkills = ['en', 'fr'];
  /**
   * User unregistered skills.
   *
   * @var array
   */
  protected static $unregisteredSkills = ['de', 'sq'];
  /**
   * Default language ID.
   *
   * @var string
   */
  protected $defaultLanguage = 'en';

  /**
   * Additional steps for tests set up.
   */
  protected function setUpTest() {
    $this->drupalLogin($this->rootUser);
    $this->skills = $this->container->get('local_translation.user_skills');
    $this->createLanguages();
    $this->enableTranslation('node', 'article');
    $this->enablePermissionsChecking();
    $this->enableAllowAccessBySourceLanguageSkills();
    $this->enableAutoPresetSourceLanguage();
    $this->enableFilterTranslationTabToSkills();
    $this->drupalLogout();
  }

  /**
   * Get array of all testing languages.
   *
   * @return array
   *   All testing langcodes array.
   */
  private static function getAllTestingLanguages() {
    return array_merge(static::$registeredSkills, static::$unregisteredSkills);
  }

  /**
   * Enable permissions checking.
   *
   * @param bool $negate
   *   An option to negate the enabling, e.g. disabling this option.
   */
  protected function enablePermissionsChecking($negate = FALSE) {
    $this->drupalPostForm(
      '/admin/config/regional/local_translation',
      ['enable_local_translation_content_permissions' => !$negate],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTextHelper('The configuration options have been saved.', FALSE);
  }

  /**
   * Enable "Filter translation tab to users translation skills" feature.
   *
   * @param bool $negate
   *   An option to negate the enabling, e.g. disabling this option.
   */
  protected function enableFilterTranslationTabToSkills($negate = FALSE) {
    $this->drupalPostForm(
      '/admin/config/regional/local_translation',
      ['enable_filter_translation_tab_to_skills' => !$negate],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTextHelper('The configuration options have been saved.', FALSE);
  }

  /**
   * Enable preseting source language to the users translation skills.
   *
   * @param bool $negate
   *   An option to negate the enabling, e.g. disabling this option.
   */
  protected function enableAutoPresetSourceLanguage($negate = FALSE) {
    $this->drupalPostForm(
      '/admin/config/regional/local_translation',
      ['enable_auto_preset_source_language_by_skills' => !$negate],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTextHelper('The configuration options have been saved.', FALSE);
  }

  /**
   * Enable limiting source language by a registered source skill.
   *
   * @param bool $negate
   *   An option to negate the enabling, e.g. disabling this option.
   */
  protected function enableAllowAccessBySourceLanguageSkills($negate = FALSE) {
    $this->drupalPostForm(
      '/admin/config/regional/local_translation',
      ['enable_access_by_source_skills' => !$negate],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTextHelper('The configuration options have been saved.', FALSE);
  }

  /**
   * Change language settings for entity types.
   *
   * @param string $category
   *   Entity category (e.g. node).
   * @param string $subcategory
   *   Entity subcategory (e.g. article).
   */
  protected function enableTranslation($category, $subcategory) {
    $this->drupalPostForm('admin/config/regional/content-language', [
      "entity_types[$category]"                                                   => 1,
      "settings[$category][$subcategory][translatable]"                           => 1,
      "settings[$category][$subcategory][settings][language][language_alterable]" => 1,
    ], 'Save configuration');
    \Drupal::entityTypeManager()->clearCachedDefinitions();
  }

  /**
   * Create testing node.
   *
   * @param null|int|string $author_id
   *   Optional. Author's user ID.
   *
   * @return int
   *   Node ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTestNode($author_id = NULL) {
    $values = ['type' => 'article', 'title' => $this->randomString()];
    if (!empty($author_id)) {
      $values['uid'] = $author_id;
    }
    $this->assertEqual(1, Node::create($values)->save());
    // Ensure node we've created is exists.
    $this->assertNotNull(Node::load(1));
    return 1;
  }

  /**
   * Add test translations to the node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   * @param null|int|string $author_id
   *   Optional. Author's user ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addTestTranslations(Node $node, $author_id = NULL) {
    $values = ['title' => $this->randomString()];
    if (!empty($author_id)) {
      $values['uid'] = $author_id;
    }

    foreach (static::getAllTestingLanguages() as $language) {
      // Skip default language.
      if ($language === $this->defaultLanguage) {
        continue;
      }
      $node->addTranslation($language, $values)->save();
    }
  }

  /**
   * Register translation skills for testing.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function registerTestSkills() {
    $this->skills->addSkill(static::$registeredSkills);
    foreach (static::$registeredSkills as $skill) {
      $this->assertTrue($this->skills->userHasSkill($skill));
    }
  }

  /**
   * Create additional languages for testing.
   */
  protected function createLanguages() {
    try {
      foreach (static::getAllTestingLanguages() as $language) {
        if ($language === $this->defaultLanguage) {
          continue;
        }
        $this->assertEqual(1, ConfigurableLanguage::createFromLangcode($language)->save());
      }
    }
    catch (EntityStorageException $e) {
      $this->fail('Additional languages have not been created');
    }
  }

  /**
   * Helper wrapper for the response code assertion.
   *
   * @param int $code
   *   Expected response code.
   * @param bool $negate
   *   Optional. Flag for whether checking code equality or NOT equality.
   *   Defaults to FALSE.
   */
  protected function assertResponseCode($code, $negate = FALSE) {
    if (!$negate) {
      $this->assertSession()->statusCodeEquals($code);
    }
    else {
      $this->assertSession()->statusCodeNotEquals($code);
    }
  }

}
