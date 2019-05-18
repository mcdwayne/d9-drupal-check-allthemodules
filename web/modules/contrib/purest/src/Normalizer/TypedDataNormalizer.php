<?php

namespace Drupal\purest\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\serialization\Normalizer\TypedDataNormalizer as SerializationTypedDataNormalizer;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\file\Entity\File;
use Drupal\link\LinkItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Converts typed data objects to arrays.
 */
class TypedDataNormalizer extends SerializationTypedDataNormalizer {

  /**
   * The normalizer used to normalize the typed data.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   */
  protected $serializer;

  /**
   * Serializing parent bool.
   *
   * @var bool
   */
  protected $serializingParent = FALSE;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // Check typed data normalizer is allowed in Purest main config.
    $config = \Drupal::service('config.factory')->get('purest.settings');
    $normalizers_on = $config->get('normalize');

    if ($normalizers_on !== NULL && !$normalizers_on) {
      return FALSE;
    }

    if ($this->serializingParent) {
      $this->serializingParent = FALSE;
      // Let parent handle it.
      return FALSE;
    }

    return parent::supportsNormalization($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {

    /* @var $object \Drupal\Core\TypedData\TypedDataInterface */
    if (!$this->serializer) {
      $this->serializer = \Drupal::service('serializer');
    }

    $this->serializingParent = TRUE;
    $value = $this->serializer->normalize($object, $format, $context);
    $this->serializingParent = FALSE;

    // If this is a field with never more then 1 value, show the first value.
    if ($object instanceof FieldItemListInterface) {
      $cardinality = $object->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
      if ($cardinality === 1) {
        if (isset($value[0])) {
          $value = $value[0];
        }
        else {
          $value = NULL;
        }
      }
    }

    // If the value is an associative array with 'value' as only key,
    // return the value of 'value'.
    if (is_array($value) && isset($value['value']) && !isset($value['end_value'])) {
      return $value['value'];
    }

    // Handle path alias fields.
    if (is_array($value) && isset($value['alias'])) {
      return $value['alias'];
    }

    if ($object instanceof ImageItem) {
      $output = [
        'uuid' => $value['target_uuid'],
        'styles' => [],
      ];
      $allow_props = ['uuid', 'alt', 'title'];

      foreach ($value as $property_name => $property) {
        $item_value = $this->serializer->normalize($property, $format, $context);

        if (in_array($property_name, $allow_props) && $property) {
          $output[$property_name] = $item_value;
        }
      }

      if (isset($value['target_id'])) {
        $file = File::load($value['target_id']);
        $file_uri = $file->getFileUri();
        $output['mime'] = $file->getMimeType();

        $output['styles']['original'] = [
          'url' => file_create_url($file_uri),
          'width' => (int) $object->width,
          'height' => (int) $object->height,
        ];

        if ($output['mime'] === 'image/svg+xml') {
          return $output;
        }

        $styles = ImageStyle::loadMultiple();

        foreach ($styles as $key => $style) {
          $dimensions = [
            'width' => (int) $object->width,
            'height' => (int) $object->height,
          ];

          $style->transformDimensions($dimensions, $file_uri);

          $output['styles'][$key] = [
            'url' => $style->buildUrl($file_uri),
          ];

          if (!empty($dimensions['height']) && !empty($dimensions['width'])) {
            $output['styles'][$key]['height'] = (int) $dimensions['height'];
            $output['styles'][$key]['width'] = (int) $dimensions['width'];
          }
        }
      }

      return $output;
    }

    // Entity reference fields.
    if ($object instanceof EntityReferenceItem) {
      if (isset($value['target_type']) && in_array($value['target_type'], [
        'node',
        'taxonomy_term',
        'view',
      ])) {
        $value['purest_type'] = 'entity_reference';

        return $value;
      }
    }

    if ($object instanceof LinkItemInterface) {
      return [
        'url' => $object->getUrl()->toString(),
        'external' => $object->isExternal(),
      ];
    }

    return $value;
  }

}
