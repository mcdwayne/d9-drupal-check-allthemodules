<?php

namespace Drupal\entity_normalization\Normalizer;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_normalization\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * Normalizes list of fields using the entity_normalization definition.
 */
class FieldItemListNormalizer extends NormalizerBase implements ContextAwareNormalizerInterface, NormalizerAwareInterface {

  use NormalizerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemListInterface::class;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $object */

    $fieldDefinition = $object->getFieldDefinition();

    $nName = $context['field_config']->getNormalizerName();
    $normalizer = NULL;
    if ($nName !== NULL && $this->container->has($nName)) {
      $normalizer = $this->container->get($nName);
    }

    $cardinality = $fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $result = [];
    if ($object->isEmpty()) {
      if ($normalizer !== NULL && $normalizer->supportsNormalization($object, $format, $context)) {
        return $normalizer->normalize($object, $format, $context);
      }
      if ($cardinality !== 1) {
        return [];
      }
      switch ($fieldDefinition->getType()) {
        case 'boolean':
          return FALSE;

        default:
          return NULL;
      }
    }

    if ($object instanceof EntityReferenceFieldItemListInterface && !$object instanceof FileFieldItemList) {
      $list = $object->referencedEntities();
    }
    else {
      $list = $object;
    }

    foreach ($list as $entity) {
      if ($normalizer !== NULL && $normalizer->supportsNormalization($entity, $format, $context)) {
        $normalizedValue = $normalizer->normalize($entity, $format, $context);
      }
      else {
        $normalizedValue = $this->normalizer->normalize($entity, $format, $context);
      }
      if (count($normalizedValue) === 1 && isset($normalizedValue['value'])) {
        $normalizedValue = $normalizedValue['value'];
      }
      if ($cardinality === 1) {
        $result = $normalizedValue;
      }
      else {
        $result[] = $normalizedValue;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, array $context = []) {
    return isset($context['field_config']) &&
      $context['field_config'] instanceof FieldConfigInterface &&
      parent::supportsNormalization($data, $format);
  }

}
