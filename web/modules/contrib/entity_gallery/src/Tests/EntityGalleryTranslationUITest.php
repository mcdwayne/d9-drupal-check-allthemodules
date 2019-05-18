<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\Tests\ContentTranslationUITestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\entity_gallery\Entity\EntityGallery;
use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Entity Gallery Translation UI.
 *
 * @group entity_gallery
 */
class EntityGalleryTranslationUITest extends ContentTranslationUITestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * {inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'theme',
    'route',
    'timezone',
    'url.path.parent',
    'url.query_args:_wrapper_format',
    'user'
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'language', 'content_translation', 'entity_gallery', 'datetime', 'field_ui', 'help');

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  protected function setUp() {
    $this->entityTypeId = 'entity_gallery';
    $this->bundle = 'article';
    parent::setUp();

    // Ensure the help message is shown even with prefixed paths.
    $this->drupalPlaceBlock('help_block', array('region' => 'content'));

    // Display the language selector.
    $this->drupalLogin($this->administrator);
    $edit = array('language_configuration[language_alterable]' => TRUE);
    $this->drupalPostForm('admin/structure/gallery-types/manage/article', $edit, t('Save gallery type'));
    $this->drupalLogin($this->translator);
  }

  /**
   * {@inheritdoc}
   */
  function setupBundle() {
    parent::setupBundle();
    // Create Basic page and Article entity gallery types.
    $this->drupalCreateGalleryType(array(
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ));
    $this->drupalCreateGalleryType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Tests the basic translation UI.
   */
  public function testTranslationUI() {
    parent::testTranslationUI();
    $this->doUninstallTest();
  }

  /**
   * Tests changing the published status on an entity gallery without fields.
   */
  public function testPublishedStatusNoFields() {
    // Test changing the published status of an article without fields.
    $this->drupalLogin($this->administrator);
    // Delete all fields.
    $this->drupalGet('admin/structure/gallery-types/manage/article/fields');
    $this->drupalPostForm('admin/structure/gallery-types/manage/article/fields/entity_gallery.article.' . $this->fieldName . '/delete', array(), t('Delete'));

    // Add an entity gallery.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = array('title' => array(array('value' => $this->randomMachineName())));
    $entity_id = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity = entity_load($this->entityTypeId, $entity_id, TRUE);

    // Add a content translation.
    $langcode = 'fr';
    $language = ConfigurableLanguage::load($langcode);
    $values[$langcode] = array('title' => array(array('value' => $this->randomMachineName())));

    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode
    ], array('language' => $language));
    $this->drupalPostForm($add_url, $this->getEditValues($values, $langcode), t('Save and unpublish (this translation)'));

    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $translation = $entity->getTranslation($langcode);
    // Make sure we unpublished the entity gallery correctly.
    $this->assertFalse($this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer entity galleries', "edit any $this->bundle entity galleries"));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return array('administer entity galleries', 'create article entity galleries');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), array('access administration pages', 'administer entity gallery types', 'administer entity_gallery fields', 'access content overview', 'bypass entity gallery access', 'administer languages', 'administer themes', 'view the administration theme'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return array('title' => array(array('value' => $this->randomMachineName()))) + parent::getNewEntityValues($langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubmitAction(EntityInterface $entity, $langcode) {
    if ($entity->getTranslation($langcode)->isPublished()) {
      return t('Save and keep published') . $this->getFormSubmitSuffix($entity, $langcode);
    }
    else {
      return t('Save and keep unpublished') . $this->getFormSubmitSuffix($entity, $langcode);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestPublishedStatus() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();

    $actions = array(
      t('Save and keep published'),
      t('Save and unpublish'),
    );

    foreach ($actions as $index => $action) {
      // (Un)publish the entity gallery translations and check that the
      // translation statuses are (un)published accordingly.
      foreach ($this->langcodes as $langcode) {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalPostForm($url, array(), $action . $this->getFormSubmitSuffix($entity, $langcode), $options);
      }
      $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
      foreach ($this->langcodes as $langcode) {
        // The entity gallery is created as unpublished thus we switch to the
        // published status first.
        $status = !$index;
        $translation = $entity->getTranslation($langcode);
        $this->assertEqual($status, $this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestAuthoringInfo() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();
    $values = array();

    // Post different base field information for each translation.
    foreach ($this->langcodes as $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      );
      $edit = array(
        'uid[0][target_id]' => $user->getUsername(),
        'created[0][value][date]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'created[0][value][time]' => format_date($values[$langcode]['created'], 'custom', 'H:i:s'),
      );
      $options = array('language' => $languages[$langcode]);
      $url = $entity->urlInfo('edit-form', $options);
      $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode), $options);
    }

    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      $translation = $entity->getTranslation($langcode);
      $metadata = $this->manager->getTranslationMetadata($translation);
      $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly stored.');
    }
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  public function testTranslationLinkTheme() {
    $this->drupalLogin($this->administrator);
    $article = $this->drupalCreateEntityGallery(array('type' => 'article', 'langcode' => $this->langcodes[0]));

    // Set up Seven as the admin theme and use it for entity gallery editing.
    $this->container->get('theme_handler')->install(array('seven'));
    $edit = array();
    $edit['admin_theme'] = 'seven';
    $edit['use_admin_theme'] = TRUE;
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('gallery/' . $article->id() . '/translations');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Translation uses admin theme if edit is admin.');

    // Turn off admin theme for editing, assert inheritance to translations.
    $edit['use_admin_theme'] = FALSE;
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('gallery/' . $article->id() . '/translations');
    $this->assertNoRaw('core/themes/seven/css/base/elements.css', 'Translation uses frontend theme if edit is frontend.');

    // Assert presence of translation page itself (vs. DisabledBundle below).
    $this->assertResponse(200);
  }

  /**
   * Tests that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabledBundle = $this->randomMachineName();
    $this->drupalCreateGalleryType(array('type' => $disabledBundle, 'name' => $disabledBundle));

    // Create an entity gallery for each bundle.
    $entity_gallery = $this->drupalCreateEntityGallery(array(
      'type' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));

    // Make sure that nothing was inserted into the {content_translation} table.
    $rows = db_query('SELECT egid, count(egid) AS count FROM {entity_gallery_field_data} WHERE type <> :type GROUP BY egid HAVING count(egid) >= 2', array(':type' => $this->bundle))->fetchAll();
    $this->assertEqual(0, count($rows));

    // Ensure the translation tab is not accessible.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/translations');
    $this->assertResponse(403);
  }

  /**
   * Tests that translations are rendered properly.
   */
  public function testTranslationRendering() {
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity_gallery = \Drupal::entityManager()->getStorage($this->entityTypeId)->load($this->entityId);

    // Create translations.
    foreach (array_diff($this->langcodes, array($default_langcode)) as $langcode) {
      $values[$langcode] = $this->getNewEntityValues($langcode);
      $translation = $entity_gallery->addTranslation($langcode, $values[$langcode]);
      // Publish the translation.
      $translation->setPublished(TRUE);
    }
    $entity_gallery->save();

    // Test that the entity gallery page displays the correct translations.
    $this->doTestTranslations('gallery/' . $entity_gallery->id(), $values);

    // Test that the entity gallery page has the correct alternate hreflang links.
    $this->doTestAlternateHreflangLinks($entity_gallery->urlInfo());
  }

  /**
   * Tests that the given path displays the correct translation values.
   *
   * @param string $path
   *   The path to be tested.
   * @param array $values
   *   The translation values to be found.
   */
  protected function doTestTranslations($path, array $values) {
    $languages = $this->container->get('language_manager')->getLanguages();
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($path, array('language' => $languages[$langcode]));
      $this->assertText($values[$langcode]['title'][0]['value'], format_string('The %langcode entity gallery translation is correctly displayed.', array('%langcode' => $langcode)));
    }
  }

  /**
   * Tests that the given path provides the correct alternate hreflang links.
   *
   * @param \Drupal\Core\Url $url
   *   The path to be tested.
   */
  protected function doTestAlternateHreflangLinks(Url $url) {
    $languages = $this->container->get('language_manager')->getLanguages();
    $url->setAbsolute();
    $urls = [];
    foreach ($this->langcodes as $langcode) {
      $language_url = clone $url;
      $urls[$langcode] = $language_url->setOption('language', $languages[$langcode]);
    }
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($urls[$langcode]);
      foreach ($urls as $alternate_langcode => $language_url) {
        // Retrieve desired link elements from the HTML head.
        $links = $this->xpath('head/link[@rel = "alternate" and @href = :href and @hreflang = :hreflang]',
          array(':href' => $language_url->toString(), ':hreflang' => $alternate_langcode));
        $this->assert(isset($links[0]), format_string('The %langcode entity gallery translation has the correct alternate hreflang link for %alternate_langcode: %link.', array('%langcode' => $langcode, '%alternate_langcode' => $alternate_langcode, '%link' => $url->toString())));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubmitSuffix(EntityInterface $entity, $langcode) {
    if (!$entity->isNew() && $entity->isTranslatable()) {
      $translations = $entity->getTranslationLanguages();
      if ((count($translations) > 1 || !isset($translations[$langcode])) && ($field = $entity->getFieldDefinition('status'))) {
        return ' ' . ($field->isTranslatable() ? t('(this translation)') : t('(all translations)'));
      }
    }
    return '';
  }

  /**
   * Tests uninstalling content_translation.
   */
  protected function doUninstallTest() {
    // Delete all the entity galleries so there is no data.
    $entity_galleries = EntityGallery::loadMultiple();
    foreach ($entity_galleries as $entity_gallery) {
      $entity_gallery->delete();
    }
    $language_count = count(\Drupal::configFactory()->listAll('language.content_settings.'));
    \Drupal::service('module_installer')->uninstall(['content_translation']);
    $this->rebuildContainer();
    $this->assertEqual($language_count, count(\Drupal::configFactory()->listAll('language.content_settings.')), 'Languages have been fixed rather than deleted during content_translation uninstall.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();
    $type_name = entity_gallery_get_type_label($entity);

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('<em>Edit @type</em> @title [%language translation]', array(
          '@type' => $type_name,
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ));
        $this->assertRaw($title);
      }
    }
  }

}
