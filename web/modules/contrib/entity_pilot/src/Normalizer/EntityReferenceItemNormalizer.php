<?php

namespace Drupal\entity_pilot\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer as BaseEntityReferenceItemNormalizer;
use Drupal\serialization\EntityResolver\EntityResolverInterface;

/**
 * Lets the entity-reference resolver work with unsaved entities.
 */
class EntityReferenceItemNormalizer extends BaseEntityReferenceItemNormalizer {

  /**
   * Parent normalizer.
   *
   * @var \Drupal\hal\Normalizer\EntityReferenceItemNormalizer
   */
  protected $parentNormalizer;

  /**
   * {@inheritdoc}
   */
  public function __construct(LinkManagerInterface $link_manager, EntityResolverInterface $entity_resolver, BaseEntityReferenceItemNormalizer $parent_normalizer, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($link_manager, $entity_resolver, $entityTypeManager);
    $this->parentNormalizer = $parent_normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    if ($entity = $this->entityResolver->resolve($this, $data, $target_type)) {
      // The exists plugin manager may nominate an existing entity to use here.
      if ($id = $entity->id()) {
        return ['target_id' => $id];
      }
      return ['entity' => $entity];
    }
    // We didn't find anything, but we don't handle config entities (where there
    // is no UUID), so defer to the parent normalizer in hal module.
    return $this->parentNormalizer->constructValue($data, $context);
  }

}
