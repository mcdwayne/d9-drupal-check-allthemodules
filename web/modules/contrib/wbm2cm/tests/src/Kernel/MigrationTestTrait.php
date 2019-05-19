<?php

namespace Drupal\Tests\wbm2cm\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

trait MigrationTestTrait {

  use ContentGenerationTrait;

  /**
   * The storage handler for the entity type under test.
   *
   * This is expected to be set by consumers' setUp() methods.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a set of revisions of a single entity.
   *
   * Each revision will be translated into every available language, and each
   * translation will be assigned a randomly chosen moderation state.
   *
   * @param int $number
   *   (optional) How many revisions to create. Defaults to 50.
   *
   * @return array
   *   An array of arrays, keyed by revision ID. Each inner array is a set of
   *   assigned moderation states, keyed by translation language.
   */
  protected function createRevisions($number = 50) {
    $keys = $this->storage->getEntityType()->getKeys();

    $values = [
      $keys['bundle'] => $this->randomBundle(),
      $keys['label'] => $this->randomMachineName(),
    ];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->create($values);
    $expectations = $this->populate($entity, $this->generateRevisionMatrix($number));

    return $expectations;
  }

  /**
   * Translates an entity into every available language.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to translate.
   *
   * @return string[]
   *   Every language into which the entity has been translated.
   */
  protected function translate(ContentEntityInterface $entity) {
    $label_key = $entity->getEntityType()->getKey('label');

    $translation_languages = array_keys($entity->getTranslationLanguages());

    $needed_languages = $this->container
      ->get('entity_type.manager')
      ->getStorage('configurable_language')
      ->getQuery()
      ->condition('locked', FALSE)
      ->condition('id', $translation_languages, 'NOT IN')
      ->execute();

    foreach ($needed_languages as $language) {
      $entity
        ->addTranslation($language)
        ->getTranslation($language)
        ->set($label_key, $this->randomMachineName());

      array_push($translation_languages, $language);
    }
    return $translation_languages;
  }

  /**
   * Executes a migration step for a particular entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $step
   *   Which step to execute. Can be one of 'save', 'clear', or 'restore'.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The executed migration.
   */
  protected function execute($entity_type_id, $step) {
    $migration_id = "wbm2cm_$step:$entity_type_id";

    $migration = $this->container
      ->get('plugin.manager.migration')
      ->createInstance($migration_id);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    return $migration;
  }

  /**
   * Returns a randomly chosen bundle ID of the entity type under test.
   *
   * @return mixed
   *   A randomly chosen bundle ID.
   */
  protected function randomBundle() {
    $entity_type_id = $this->storage->getEntityType()->getBundleEntityType();
    return $this->randomEntity($entity_type_id);
  }

  abstract protected function prepareDatabase();

}
