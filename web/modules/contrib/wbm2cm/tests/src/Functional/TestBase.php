<?php

namespace Drupal\Tests\wbm2cm\Functional;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\wbm2cm\Kernel\MigrationTestTrait;
use Drupal\Tests\BrowserTestBase;

abstract class TestBase extends BrowserTestBase {

  use MigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'wbm2cm',
    'workbench_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('hu')->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDatabase() {
    // TODO: Put this bullshit in the source plugin configuration.
    $db = $this->getDatabaseConnection()->getConnectionOptions();
    $db['prefix']['default'] = $this->databasePrefix;
    Database::addConnectionInfo('migrate', 'default', $db);
  }

  /**
   * Migrates from Workbench Moderation to Content Moderation.
   */
  protected function doMigration() {
    wbm2cm_install();

    $entity_type_id = $this->storage->getEntityTypeId();
    $this->execute($entity_type_id, 'save');
    $this->execute($entity_type_id, 'clear');

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall(['workbench_moderation']);
    $module_installer->install(['content_moderation']);

    // Ensure we don't have a stale definition of the moderation_state field in
    // our memory space.
    $this->container
      ->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    // Ensure we don't have a stale static entity cache in our memory space.
    $this->storage->resetCache();

    $this->execute($entity_type_id, 'restore');

    // Reload the storage in order to clear any cached table mappings.
    $this->storage = $this->container
      ->get('entity_type.manager')
      ->getStorage($entity_type_id);
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  protected function createEntity() {
    $values = [];

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->storage->getEntityType();

    if ($entity_type->hasKey('bundle')) {
      $values[ $entity_type->getKey('bundle') ] = $this->randomBundle();
    }
    if ($entity_type->hasKey('label')) {
      $values[ $entity_type->getKey('label') ] = $this->randomMachineName(16);
    }

    return $this->storage->create($values);
  }

  /**
   * Returns, or creates, an entity translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to translate.
   * @param string $language
   *   The translation language code.
   *
   * @return ContentEntityInterface
   *   The translated entity.
   */
  protected function translate(ContentEntityInterface $entity, $language) {
    if ($entity->hasTranslation($language)) {
      $translation = $entity->getTranslation($language);
    }
    else {
      $translation = $entity->addTranslation($language)->getTranslation($language);

      $label_key = $entity->getEntityType()->getKey('label');
      if ($label_key) {
        $translation->set($label_key, $this->randomMachineName(16));
      }
    }
    return $translation;
  }

  /**
   * Migrates an entity with only one revision and no translations.
   */
  public function testSingleUntranslatedRevision() {
    $moderation_state = $this->randomEntity('moderation_state');

    $entity = $this->createEntity()->set('moderation_state', $moderation_state);
    $this->storage->save($entity);

    $this->doMigration();

    $this->assertSame(
      $moderation_state,
      $this->storage->load($entity->id())->moderation_state->value
    );
  }

  /**
   * Migrates an entity with translations, but only one revision.
   */
  public function testSingleTranslatedRevision() {
    $entity = $this->createEntity();

    $states = [
      'en' => $this->randomEntity('moderation_state'),
      'fr' => $this->randomEntity('moderation_state'),
      'hu' => $this->randomEntity('moderation_state'),
    ];
    foreach ($states as $language => $state) {
      $this->translate($entity, $language)->set('moderation_state', $state);
    }
    $this->storage->save($entity);

    $this->doMigration();

    /** @var ContentEntityInterface $entity */
    $entity = $this->storage->load($entity->id());

    foreach ($states as $language => $state) {
      // The only revision should also be the default revision.
      $this->assertTrue($entity->isDefaultRevision());
      $this->assertSame($state, $entity->getTranslation($language)->moderation_state->value);
    }
  }

  /**
   * Migrates an entity with several revisions, but no translations.
   */
  public function testMultipleUntranslatedRevisions() {
    $entity = $this->createEntity();

    $default_vid = NULL;
    $moderation_states = [];
    while (count($moderation_states) < 5) {
      $entity->setNewRevision();

      // The third revision will arbitrarily be the default revision.
      if (count($moderation_states) === 2) {
        $entity->isDefaultRevision(TRUE);
      }

      $entity->set('moderation_state', $this->randomEntity('moderation_state'));
      $this->storage->save($entity);
      $vid = (int) $entity->getRevisionId();

      if ($entity->isDefaultRevision()) {
        $default_vid = $vid;
      }
      $moderation_states[$vid] = $entity->moderation_state->target_id;
    }
    // Ensure that we chose a default revision.
    $this->assertInternalType('integer', $default_vid);

    $this->doMigration();

    foreach ($moderation_states as $vid => $moderation_state) {
      /** @var ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      if ($vid === $default_vid) {
        $this->assertTrue($revision->isDefaultRevision());
      }
      $this->assertSame($moderation_state, $revision->moderation_state->value);
    }
  }

  /**
   * Migrates an entity with several revisions, all of which are translated.
   */
  public function testMultipleTranslatedRevisions() {
    $entity = $this->createEntity();

    $default_vid = NULL;
    $revisions = [];
    while (count($revisions) < 5) {
      $entity->setNewRevision();

      // The fourth revision will arbitrarily be the default revision.
      if (count($revisions) === 3) {
        $entity->isDefaultRevision(TRUE);
      }

      $moderation_states = [];
      foreach (['en', 'fr', 'hu'] as $language) {
        $moderation_states[$language] = $this->randomEntity('moderation_state');
        $this->translate($entity, $language)->set('moderation_state', $moderation_states[$language]);
      }
      $this->storage->save($entity);
      $vid = (int) $entity->getRevisionId();
      if ($entity->isDefaultRevision()) {
        $default_vid = $vid;
      }
      $revisions[$vid] = $moderation_states;
    }
    // Ensure we set a default revision.
    $this->assertInternalType('integer', $default_vid);

    $this->doMigration();

    foreach ($revisions as $vid => $moderation_states) {
      /** @var ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      if ($vid === $default_vid) {
        $this->assertTrue($revision->isDefaultRevision());
      }

      foreach ($moderation_states as $language => $moderation_state) {
        $this->assertSame($moderation_state, $revision->getTranslation($language)->moderation_state->value);
      }
    }
  }

  /**
   * Migrates an entity with several revisions and translations.
   */
  public function testMultipleTranslatedMixedRevisions() {
    $entity = $this->createEntity();
    $revisions = [];

    // The revision has translations in English, French, and Hungarian, but
    // only English and Hungarian have a moderation state.
    $entity->setNewRevision();
    $entity->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->translate($entity, 'fr');
    $this->translate($entity, 'hu')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->storage->save($entity);
    $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;
    $revisions[$vid]['hu'] = $entity->getTranslation('hu')->moderation_state->target_id;

    // The next revision assigns moderation states to French and Hungarian, but
    // not English.
    $entity->setNewRevision();
    $entity->getTranslation('fr')->set('moderation_state', $this->randomEntity('moderation_state'));
    $entity->getTranslation('hu')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->storage->save($entity);
    $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;
    $revisions[$vid]['hu'] = $entity->getTranslation('hu')->moderation_state->target_id;

    // The next revision assigns moderation states to English and French, but
    // not Hungarian.
    $entity->setNewRevision();
    $entity->set('moderation_state', $this->randomEntity('moderation_state'));
    $entity->getTranslation('hu')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->storage->save($entity);
    $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;
    $revisions[$vid]['hu'] = $entity->getTranslation('hu')->moderation_state->target_id;

    $this->doMigration();

    foreach ($revisions as $vid => $moderation_states) {
      /** @var ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      foreach ($moderation_states as $language => $moderation_state) {
        $this->assertSame($moderation_state, $revision->getTranslation($language)->moderation_state->value);
      }
    }
  }

  /**
   * Migrates an entity with several revisions, one of which adds a translation.
   */
  public function testMultipleRevisionsWithNewTranslation() {
    $entity = $this->createEntity();
    $revisions = [];

    // The revision has translations in English and French, but not Hungarian.
    $entity->setNewRevision();
    $entity->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->translate($entity, 'fr')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->storage->save($entity);
    $initial_revision = $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;

    // The next revision adds a Hungarian translation without a moderation state
    // and assigns new moderation states to the English and French translations.
    $entity->setNewRevision();
    $entity->set('moderation_state', $this->randomEntity('moderation_state'));
    $entity->getTranslation('fr')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->translate($entity, 'hu');
    $this->storage->save($entity);
    $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;
    $revisions[$vid]['hu'] = $entity->getTranslation('hu')->moderation_state->target_id;

    // The next revision assigns new moderation states to all translations.
    $entity->setNewRevision();
    $entity->set('moderation_state', $this->randomEntity('moderation_state'));
    $entity->getTranslation('fr')->set('moderation_state', $this->randomEntity('moderation_state'));
    $entity->getTranslation('hu')->set('moderation_state', $this->randomEntity('moderation_state'));
    $this->storage->save($entity);
    $vid = $entity->getRevisionId();
    $revisions[$vid]['en'] = $entity->moderation_state->target_id;
    $revisions[$vid]['fr'] = $entity->getTranslation('fr')->moderation_state->target_id;
    $revisions[$vid]['hu'] = $entity->getTranslation('hu')->moderation_state->target_id;

    $this->doMigration();

    foreach ($revisions as $vid => $moderation_states) {
      /** @var ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      if ($vid === $initial_revision) {
        $this->assertFalse($revision->hasTranslation('hu'));
      }

      foreach ($moderation_states as $language => $moderation_state) {
        $this->assertSame($moderation_state, $revision->getTranslation($language)->moderation_state->value);
      }
    }
  }

  /**
   * Adds moderation to an entity bundle.
   *
   * @param \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $entity
   *   The bundle entity.
   *
   * @return ThirdPartySettingsInterface|\Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The bundle entity, with moderation settings added.
   */
  protected function moderate(ThirdPartySettingsInterface $entity) {
    $moderation_states = $this->container
      ->get('entity_type.manager')
      ->getStorage('moderation_state')
      ->getQuery()
      ->execute();

    return $entity
      ->setThirdPartySetting('workbench_moderation', 'enabled', TRUE)
      ->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', $moderation_states)
      ->setThirdPartySetting('workbench_moderation', 'default_moderation_state', 'draft');
  }

}
