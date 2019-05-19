<?php

namespace Drupal\Tests\wbm2cm\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;

trait ContentGenerationTrait {

  protected function pokeHoles(array $matrix) {
    foreach ($matrix as $i => $revision) {
      foreach ($revision as $language => $values) {
        // Every third revision, flip a coin to decide whether or not to clear
        // the moderation state.
        if ($i % 3 == 0) {
          mt_srand();
          if ((bool) mt_rand(0, 1)) {
            unset($values['moderation_state']);
          }
        }
        $matrix[$i][$language] = $values;
      }
    }
    return $matrix;
  }

  protected function getRevisionMatrix(ContentEntityInterface $entity) {
    $matrix = [];

    /** @var ContentEntityInterface $revision */
    foreach ($this->getRevisions($entity) as $vid => $revision) {
      $translations = array_keys($entity->getTranslationLanguages());

      foreach ($translations as $language) {
        $matrix[$vid] = $this->getFieldData($entity->getTranslation($language));
      }
    }
    return $matrix;
  }

  protected function getFieldData(ContentEntityInterface $entity) {
    return [
      'moderation_state' => $entity->moderation_state->value,
      'title' => $entity->label(),
    ];
  }

  protected function getRevisions(ContentEntityInterface $entity) {
    $entity_id = $entity->id();
    $entity_type = $entity->getEntityType();

    $revisions = $this->query($entity_type->id())
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity_id)
      ->execute();

    $this->storage->resetCache([$entity_id]);

    foreach (array_keys($revisions) as $vid) {
      yield $vid => $this->storage->loadRevision($vid);
    }
  }

  protected function populate(ContentEntityInterface $entity, array $matrix) {
    $saved_matrix = [];

    foreach ($matrix as $revision) {
      $entity->setNewRevision();

      foreach ($revision as $language => $fields) {
        if (! $entity->hasTranslation($language)) {
          $entity->addTranslation($language);
        }
        $translation = $entity->getTranslation($language);

        foreach ($fields as $field => $value) {
          $translation->set($field, $value);
        }
      }

      $this->storage->save($entity);
      $vid = $entity->getRevisionId();
      $saved_matrix[$vid] = $revision;
    }
    return $saved_matrix;
  }

  protected function generateRevisionMatrix($revisions = 50) {
    $matrix = [];

    while (count($matrix) < $revisions) {
      array_push($matrix, $this->generateRevision());
    }
    return $this->pokeHoles($matrix);
  }

  protected function generateRevision() {
    $languages = $this->query('configurable_language')
      ->condition('locked', FALSE)
      ->execute();

    $default_language = $this->container
      ->get('language_manager')
      ->getDefaultLanguage()
      ->getId();

    if (!in_array($default_language, $languages, TRUE)) {
      $languages[$default_language] = $default_language;
    }

    return array_map([$this, 'generateFieldData'], $languages);
  }

  protected function generateFieldData() {
    $data = [
      'moderation_state' => $this->randomEntity('moderation_state'),
    ];
    $label_key = $this->storage->getEntityType()->getKey('label');
    if ($label_key) {
      $data[$label_key] = $this->randomMachineName(16);
    }
    return $data;
  }

  protected function randomEntity($entity_type) {
    $items = $this->query($entity_type)->execute();
    shuffle($items);

    return reset($items);
  }

  /**
   * @param $entity_type
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  protected function query($entity_type) {
    return $this->container
      ->get('entity_type.manager')
      ->getStorage($entity_type)
      ->getQuery();
  }

}
