<?php

namespace Drupal\entity_resource_layer\Normalizer;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_resource_layer\EntityResourceLayerManager;
use Drupal\entity_resource_layer\Exception\EntityResourceFieldException;
use Drupal\entity_resource_layer\Exception\EntityResourceInvalidFieldsException;
use Drupal\entity_resource_layer\Exception\EntityResourceMultipleException;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Normalizer for entities.
 *
 * This normalizer calls the entity adaptors.
 *
 * @package Drupal\entity_resource_layer\Normalizer
 */
class EntityAdaptorNormalizer extends ContentEntityNormalizer {

  use StringTranslationTrait;

  /**
   * The adaptor manager.
   *
   * @var \Drupal\entity_resource_layer\EntityResourceLayerManager
   */
  protected $resourceLayerManager;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * EntityAdaptorNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\entity_resource_layer\EntityResourceLayerManager $adaptorManager
   *   The entity plugin adaptor manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RequestStack $requestStack, EntityResourceLayerManager $adaptorManager) {
    parent::__construct($entity_manager);
    $this->resourceLayerManager = $adaptorManager;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $object */
    $layers = $this->resourceLayerManager->getAdaptors(
      $object->getEntityTypeId(),
      $object->bundle(),
      $this->currentRequest->query->get('api')
    );

    // If no layers fallback to default handling.
    if (empty($layers)) {
      return parent::normalize($object, $format, $context);
    }

    // Get the highest priority adaptor. We will use the field mapping and
    // field visibility from this, as combining does not make much sense.
    $firstLayer = reset($layers);

    $context += ['account' => NULL];

    // First fetch and normalize all fields that are visible.
    $attributes = [];
    foreach ($firstLayer->getVisibleFields($object) as $fieldName) {
      $field = $object->get($fieldName);

      if ($field->access('view', $context['account'])) {
        $attributes[$fieldName] = $this->serializer->normalize($field, $format, $context);
      }
    }

    // Embed set referenced entities.
    $attributes = $firstLayer->embedReferences($attributes, $object, $format, $context);

    // Run all layers specific adaptions.
    foreach ($layers as $adaptor) {
      $attributes = $adaptor->adaptOutgoing($attributes, $object);
    }

    // Map field names.
    $attributes = $firstLayer->mapFieldsOutgoing($attributes);

    // Allow entities to be converted to a single data.
    if ($field = $firstLayer->getFocus()) {
      $attributes = $attributes[$field];
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entityType = $this->determineEntityTypeId($class, $context);
    $entityTypeDefinition = $this->getEntityTypeDefinition($entityType);
    $bundle = NULL;

    if ($entityTypeDefinition->hasKey('bundle') && $entityTypeDefinition->entityClassImplements(FieldableEntityInterface::class)) {
      $bundleData = $this->extractBundleData($data, $entityTypeDefinition);
      $bundle = array_values($bundleData)[0];
      $data[array_keys($bundleData)[0]] = $bundle;
    }

    $layers = $this->resourceLayerManager->getAdaptors($entityType, $bundle,
      $this->currentRequest->query->get('api'));
    // If no adaptors fallback to default handling.
    if (empty($layers)) {
      return parent::denormalize($data, $class, $format, $context);
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    $fieldDefinitions = $fieldManager->getFieldDefinitions($entityType, $bundle ?: $entityType);

    // Get the highest priority adaptor. We will use the field mapping from
    // this, as combining does not make much sense.
    $firstLayer = reset($layers);
    $fieldMap = $firstLayer->getFieldsMapping(array_keys($fieldDefinitions));

    // Log the request data but omit the sensitive information.
    $logData = $data;
    foreach ($firstLayer->getSensitiveFields() as $sensitive) {
      $sensitive = $fieldMap[$sensitive];
      if (array_key_exists($sensitive, $logData)) {
        unset($logData[$sensitive]);
      }
    }

    \Drupal::logger('rest')->info('Request: [' . get_class($firstLayer) . '];\n Data: ' . json_encode($logData) . '; ');

    $data = $firstLayer->mapFieldsIncoming($data, $bundle);

    // Run all adaptors specific adaptions.
    foreach ($layers as $adaptor) {
      $data = $adaptor->adaptIncoming($data);
    }

    // Validate field existence.
    $exception = new EntityResourceMultipleException($this->t('Cannot process @entity. Unrecognized fields detected.', ['@entity' => $entityTypeDefinition->getLabel()]));
    foreach (array_keys($data) as $fieldName) {
      if (!array_key_exists($fieldName, $fieldDefinitions)) {
        $exception->addException(new EntityResourceFieldException($this->t('Unrecognized field.'), $fieldName, 'FIELD_UNKNOWN'));
      }
    }

    if ($exception->hasException()) {
      throw $exception;
    }

    return parent::denormalize($data, $class, $format, $context);
  }

}
