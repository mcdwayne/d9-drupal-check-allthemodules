<?php
/**
 * @file
 * Contains \Drupal\collect_client\Plugin\collect_client\EntityItemHandler.
 */

namespace Drupal\collect_client\Plugin\collect_client;

use Drupal\collect_client\CollectItem;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Serializes a captured entity to a HAL+JSON object.
 *
 * @CollectClientItemHandler(
 *   id = "entity"
 * )
 */
class EntityItemHandler extends PluginBase implements ItemHandlerInterface {

  /**
   * The mime type of the submitted data.
   */
  const MIMETYPE = 'application/json';

  /**
   * {@inheritdoc}
   */
  public function supports($item) {
    return is_array($item)
      && array_key_exists('operation', $item)
      && array_key_exists('entity', $item)
      && array_key_exists('cache_key', $item)
      && array_key_exists('date', $item);
  }

  /**
   * {@inheritdoc}
   */
  public function handle($item) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $item['entity'];
    $origin_uri = $entity->url('canonical', ['absolute' => TRUE]);

    $request = \Drupal::requestStack()->getCurrentRequest();

    if ($origin_uri == '') {
      $origin_uri = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/entity/' . $entity->getEntityType()->id();
      if ($entity->getEntityType()->hasKey('bundle')) {
        $origin_uri .= '/' . $entity->bundle();
      }
      $origin_uri .= '/' . $entity->uuid();
    }

    $schema_uri = 'http://schema.md-systems.ch/collect/0.0.1/collectjson/' . $request->getHttpHost() . '/entity/' . $entity->getEntityType()->id();

    // Exclude key bundle from the schema URI for entites that do not have it.
    if ($entity->getEntityType()->hasKey('bundle')) {
      $schema_uri .= '/' . $entity->bundle();
    }

    // Create the transfer object.
    $result = new CollectItem();
    $result->schema_uri = $schema_uri;
    $result->type = static::MIMETYPE;
    $result->uri = $origin_uri;
    $result->date = $item['date'];
    $result->cache_key = $item['cache_key'];

    /* @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer */
    $serializer = \Drupal::service('serializer');

    $values = $serializer->normalize($entity, 'collect_json');
    $fields = $serializer->normalize($entity->getFieldDefinitions(), 'json');
    // Expand field definitions with URI definitions for entity reference fields.
    $fields = collect_common_add_uri_definitions(\Drupal::entityManager(), $serializer, $entity->getFieldDefinitions(), $fields);

    $result->data = Json::encode([
      'values' => $values,
      'fields' => $fields,
      'operation' => $item['operation'],
    ]);
    return $result;
  }

}
