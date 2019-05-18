<?php

namespace Drupal\collect_common\Normalizer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * Converts the Drupal entity object to a HAL compatible structure.
 */
class CollectEntityNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * The format this Normalizer supports.
   *
   * @var string
   */
  protected $format = 'collect_json';

  /**
   * Default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs an CollectEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Default cache bin.
   */
  public function __construct(EntityManagerInterface $entity_manager, CacheBackendInterface $cache) {
    parent::__construct($entity_manager);
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $context += array(
      'account' => NULL,
    );

    $attributes = [];
    $attributes['_links']['self']['href'] = $object->url('canonical', ['absolute' => TRUE]);

    // Each field will be serialized. Consequently, confidental data might be
    // stored in the container.
    foreach ($object as $name => $field) {
      // Move the references into _links and expand it with URI.
      if ($field->getSetting('target_type') && $this->entityManager->getDefinition($field->getSetting('target_type'))->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
        $attributes['_links'][$name] = $this->serializer->normalize($field, $format, $context);
        /** @var \Drupal\Core\Entity\EntityInterface $referenced_entity */
        foreach ($field->referencedEntities() as $referenced_entity_key => $referenced_entity) {
          if ($referenced_entity->hasLinkTemplate('canonical')) {
            $attributes['_links'][$name][$referenced_entity_key]['href'] = $referenced_entity->toUrl('canonical', ['absolute' => TRUE])->toString();
          }
          // Add a UUID of the target container.
          $cached = $this->cache->get(collect_common_cache_key($referenced_entity));
          if ($cached && isset($cached->data['uuid'])) {
            $attributes['_links'][$name][$referenced_entity_key]['uuid'] = $cached->data['uuid'];
          }
        }
      }
      elseif ($field->getSetting('entity_type_ids')) {
        $fieldable_entities = [];
        foreach ($field->getSetting('entity_type_ids') as $key => $definition) {
          if ($this->entityManager->getDefinition($definition)->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
            $fieldable_entities[] = $definition;
          }
        }
        $attributes['_links'][$name] = $this->serializer->normalize($field, $format, $context);
        /** @var \Drupal\Core\Entity\EntityInterface $referenced_entity */
        foreach ($field->referencedEntities() as $referenced_entity_key => $referenced_entity) {
          if (in_array($referenced_entity->getEntityTypeId(), $fieldable_entities)) {
            if ($referenced_entity->hasLinkTemplate('canonical')) {
              $attributes['_links'][$name][$referenced_entity_key]['href'] = $referenced_entity->toUrl('canonical', ['absolute' => TRUE])->toString();
            }
            // Add a UUID of the target container.
            $cached = $this->cache->get(collect_common_cache_key($referenced_entity));
            if ($cached && isset($cached->data['uuid'])) {
              $attributes['_links'][$name][$referenced_entity_key]['uuid'] = $cached->data['uuid'];
            }
          }
        }
      }
      else {
        $attributes[$name] = $this->serializer->normalize($field, $format, $context);
      }
    }

    return $attributes;
  }

}
