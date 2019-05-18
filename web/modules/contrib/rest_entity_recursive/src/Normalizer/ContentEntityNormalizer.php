<?php

namespace Drupal\rest_entity_recursive\Normalizer;

use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts the Drupal entity object structure to a JSON array structure.
 */
class ContentEntityNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * The format that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['json_recursive'];

  /**
   * Default max depth.
   *
   * @var int
   */
  protected $defaultMaxDepth = 10;

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    // If it is root entity set current_depth and root_entity in context.
    if (!array_key_exists('current_depth', $context)) {
      $context['current_depth'] = 0;

      // Get max_depth from request.
      $requestMaxDepth = $context['request']->query->get('max_depth');

      // Set max_depth in context.
      if ($requestMaxDepth === "0") {
        $context['max_depth'] = 0;
      }
      elseif ((int) $requestMaxDepth > 0) {
        $context['max_depth'] = (int) $requestMaxDepth;
      }
      else {
        $context['max_depth'] = $this->defaultMaxDepth;
      }
    }

    /* @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();

    // Create an array of normalized fields.
    $normalized = [
      'entity_type' => [['value' => $entity_type]],
      'entity_bundle' => [['value' => $entity_bundle]],
    ];

    // Set root entity in context.
    if (empty($context['root_parent_entity'])) {
      $context['root_parent_entity'] = [
        'id' => $entity->id(),
        'type' => $entity_type,
      ];
    }

    $field_items = TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData());

    // Other normalizers can pass array of fields to exclude from processing.
    if (isset($context['settings'][$entity_type]['exclude_fields'])) {
      $excluded_fields = $context['settings'][$entity_type]['exclude_fields'];
      $field_items = array_diff_key($field_items, array_flip($excluded_fields));
    }
    foreach ($field_items as $field) {
      // Continue if the current user does not have access to view this field.
      if (!$field->access('view')) {
        continue;
      }
      $normalized[$field->getName()] = $this->serializer->normalize($field, $format, $context);
    }

    return $normalized;
  }

}
