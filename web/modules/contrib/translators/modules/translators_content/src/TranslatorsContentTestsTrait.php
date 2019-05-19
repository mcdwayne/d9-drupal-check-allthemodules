<?php

namespace Drupal\translators_content;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Trait TranslatorsContentTestsTrait.
 *
 * @package Drupal\translators_content
 */
trait TranslatorsContentTestsTrait {

  /**
   * Translator skills service.
   *
   * @var \Drupal\translators\Services\TranslatorSkills
   */
  protected $translatorSkills;
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
    $this->translatorSkills = $this->container->get('translators.skills');
    $this->createLanguages();
    $this->enableTranslation('node', 'article');
    $this->enablePermissionsChecking();
    $this->enableAllowAccessBySourceLanguageSkills();
    $this->enableAutoPresetSourceLanguage();
    $this->enableFilterTranslationOverviewToSkills();
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
      '/admin/config/regional/translators',
      ['enable_translators_content_permissions' => !$negate],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTextHelper('The configuration options have been saved.', FALSE);
  }

  /**
   * Enable filtering translation overview to users translation skills.
   *
   * @param bool $negate
   *   An option to negate the enabling, e.g. disabling this option.
   */
  protected function enableFilterTranslationOverviewToSkills($negate = FALSE) {
    $this->drupalPostForm(
      '/admin/config/regional/translators',
      ['enable_filter_translation_overview_to_skills' => !$negate],
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
      '/admin/config/regional/translators',
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
      '/admin/config/regional/translators',
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
    $this->addSkill(static::$registeredSkills);
    foreach (static::$registeredSkills as $skill) {
      $this->assertTrue($this->translatorSkills->hasSkill($skill));
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

  /**
   * Add multiple skills.
   *
   * @param array $skills
   *   Array of arrays of source->target skills.
   * @param null|\Drupal\user\Entity\User $user
   *   User to operate on.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addSkills(array $skills, $user = NULL) {
    if (is_null($user)) {
      $user = $this->userLoad(\Drupal::currentUser()->id());
    }
    foreach ($skills as $skill) {
      $this->addSkill($skill, $user);
    }
  }

  /**
   * Add translation skill.
   *
   * @param array $skill
   *   Array of source->target skills.
   * @param null|\Drupal\user\Entity\User $user
   *   User to operate on.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addSkill(array $skill, $user = NULL) {
    if (is_null($user)) {
      $user = $this->userLoad(\Drupal::currentUser()->id());
    }

    $translationSkillsField = \Drupal::config('translators.settings')
      ->get('translation_skills_field_name');

    $user->get($translationSkillsField)->appendItem([
      'language_source' => $skill[0],
      'language_target' => $skill[1],
    ]);
    $user->save();
  }

  /**
   * Load user entity by a given ID.
   *
   * @param int|string $id
   *   User ID.
   *
   * @return \Drupal\user\UserInterface|\Drupal\Core\Entity\EntityInterface|null
   *   Loaded user entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function userLoad($id) {
    \Drupal::entityTypeManager()->getStorage('user')->resetCache([$id]);
    return \Drupal::entityTypeManager()->getStorage('user')->load($id);
  }

}
