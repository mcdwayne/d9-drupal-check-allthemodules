<?php

namespace Drupal\cyr2lat\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class Cyr2LatCommands extends DrushCommands {

  /**
   * Translate single content entity.
   *
   * @command cyr2lat:translate
   * @param string $entity_type Entity type.
   * @param int $entity_id Entity ID to be translated.
   * @aliases c2l-translate
   * @usage cyr2lat:translate node 1
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function translate($entity_type, $entity_id) {
    if (empty($entity_type) || empty($entity_id)) {
      $this->output()->writeln("Required parameters missing. Check help for more info.");
      return;
    }

    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    try {
      // Get entity storage.
      $entity_storage = $entity_type_manager->getStorage($entity_type);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entity_storage->load($entity_id);

      // Translate entity.
      $this->translateEntity($entity);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->output()->writeln($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->output()->writeln($e->getMessage());
    }
  }

  /**
   * Translate all content entities of a type.
   *
   * @command cyr2lat:translate-all
   * @param string $entity_type Entity type.
   * @aliases c2l-translate-all
   * @usage cyr2lat:translate-all node
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function translateAll($entity_type) {
    if (empty($entity_type)) {
      $this->output()->writeln("Required parameters missing. Check help for more info.");
      return;
    }

    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    try {
      // Get entity storage.
      $entity_storage = $entity_type_manager->getStorage($entity_type);

      // Load all published entities.
      $entity_ids = $entity_storage->getQuery()
        ->condition('status', 1)
        ->execute();
      $entities = $entity_storage->loadMultiple($entity_ids);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      foreach ($entities as $entity_id => $entity) {
        // Translate entity.
        $this->translateEntity($entity);
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->output()->writeln($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->output()->writeln($e->getMessage());
    }


  }

  /**
   * Helper method to translate entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content Entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function translateEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\cyr2lat\Services\Cyr2LatTransliterator $transliterator */
    $transliterator = \Drupal::service('cyr2lat.transliterator');
    $transliterator->transliterateEntity($entity);
    $this->output()->writeln(dt("Entity %type:%id transliterated.", ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id()]));
  }

}