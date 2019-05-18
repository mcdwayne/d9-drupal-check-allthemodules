<?php

namespace Drupal\data_fixtures;

use Faker\Factory;
use Faker;

/**
 * Class AbstractGenerator.
 *
 * @package Drupal\data_fixtures
 */
class AbstractGenerator {

  /**
   * Dummy content generator.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * AbstractGenerator constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Create a Link.
   *
   * @param string $uri
   *   Valid url or drupal path.
   * @param string $title
   *   Link label.
   *
   * @return array
   *   Config for a link field.
   */
  public function getLink($uri = NULL, $title = NULL) {
    return [
      'uri' => $uri ?? $this->faker->url,
      'title' => $title ?? $this->faker->text(20),
    ];
  }

  /**
   * Get structured array for a formatted text.
   *
   * @param string $format
   *   Format of the text.
   * @param string|null $text
   *   Predefined text you may want to use.
   *
   * @return array
   *   Array containing the format and the text.
   */
  protected function getFormattedText($format, $text = NULL) {
    return [
      'value' => $text ?? $this->faker->realText(400),
      'format' => $format,
    ];
  }

  /**
   * Return an array of entities.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $conditions
   *   Array of conditions to use in the entity query.
   * @param int $limit
   *   Number of items to return.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|null
   *   Array of random entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRandomEntities($entity_type_id, array $conditions = [], $limit = 5) {
    static $entities;

    // We generate a hash for static storage of query results, per condition set
    // Always order conditions to mitigate risk of different hashes
    // for identical conditions.
    asort($conditions);
    $hash = md5($entity_type_id . ':' . json_encode($conditions));

    if (!isset($entities[$hash])) {
      $query = \Drupal::entityQuery($entity_type_id);

      if ($conditions) {
        foreach ($conditions as $key => $value) {
          $query->condition($key, $value);
        }
      }

      $ids = $query->execute();
      if ($ids) {
        $entities[$hash] = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($ids);
      }
    }

    shuffle($entities[$hash]);
    $sliced = array_slice($entities[$hash], 0, $limit);

    return $sliced;
  }

  /**
   * Return a media entity by the name of the file within it.
   *
   * @param string $name
   *   File name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Media entity matched to the given name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMediaByName($name) {
    static $entities;

    if (!isset($entities)) {
      $query = \Drupal::entityQuery('media');

      $ids = $query->execute();
      if ($ids) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface[] $raw_entities */
        $raw_entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($ids);

        foreach ($raw_entities as $entity) {
          // Media can be of multiple sort...
          // We currently focus on our needs with only image and file.
          /** @var \Drupal\file\FileInterface $file */
          if ($entity->hasField('field_media_image')) {
            $file = $entity->get('field_media_image')->entity;
          }
          elseif ($entity->hasField('field_media_file')) {
            $file = $entity->get('field_media_file')->entity;
          }
          else {
            return NULL;
          }

          $entities[$file->getFilename()] = $entity;
        }
      }
    }

    return $entities[$name] ?? NULL;
  }

  /**
   * Unload entities matching the given type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array|null $conditions
   *   Array of conditions to use in the entity querycd .
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unloadEntities($entity_type_id, array $conditions = NULL) {
    $query = \Drupal::entityQuery($entity_type_id);

    if ($conditions) {
      foreach ($conditions as $key => $value) {
        $query->condition($key, $value);
      }
    }

    $entity_ids = $query->execute();

    if ($entity_ids) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($entity_ids);
      \Drupal::entityTypeManager()->getStorage($entity_type_id)->delete($entities);
    }
  }

}
