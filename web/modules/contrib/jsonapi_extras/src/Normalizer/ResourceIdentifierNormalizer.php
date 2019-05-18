<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Converts the Drupal entity reference item object to a JSON:API structure.
 *
 * @internal
 */
class ResourceIdentifierNormalizer extends JsonApiNormalizerDecoratorBase {

  /**
   * The resource type repository for changes on the target resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Instantiates a ResourceIdentifierNormalizer object.
   *
   * @param \Symfony\Component\Serializer\SerializerAwareInterface|\Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface $inner
   *   The decorated normalizer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The repository.
   */
  public function __construct($inner, ResourceTypeRepositoryInterface $resource_type_repository) {
    parent::__construct($inner);
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []) {
    assert($field instanceof ResourceIdentifier);
    $normalized_output = parent::normalize($field, $format, $context);
    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type */
    $resource_type = $context['resource_object']->getResourceType();
    $enhancer = $resource_type->getFieldEnhancer($context['field_name']);
    if (!$enhancer) {
      return $normalized_output;
    }
    // Apply any enhancements necessary.
    $transformed = $enhancer->undoTransform($normalized_output->getNormalization());
    $target_id = $transformed['id'];
    // @TODO: Enhancers should utilize CacheableNormalization to infer additional cacheability from the enhancer.
    return new CacheableNormalization($normalized_output, ['target_uuid' => $target_id, 'meta' => $transformed['meta']]);
  }

}
