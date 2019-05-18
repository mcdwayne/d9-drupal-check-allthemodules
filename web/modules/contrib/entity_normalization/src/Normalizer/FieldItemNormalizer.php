<?php

namespace Drupal\entity_normalization\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_normalization\FieldConfigInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer as DefaultFieldItemNormalizer;
use Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Normalizer for field item.
 */
class FieldItemNormalizer extends DefaultFieldItemNormalizer implements ContextAwareNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Field\FieldItemInterface $object */

    if ($object instanceof StringItem
      || $object instanceof ListStringItem
      || $object instanceof DateTimeItem
    ) {
      return $object->getValue();
    }

    if ($object instanceof IntegerItem || $object instanceof TimestampItem) {
      $normalizedValue = $object->getValue();
      if (isset($normalizedValue['value'])) {
        $normalizedValue['value'] = (int) $normalizedValue['value'];
      }
      return $normalizedValue;
    }

    if ($object instanceof ImageItem) {
      $normalizedValue = [
        'target_id' => (int) $object->get('target_id')->getValue(),
        'width' => (int) $object->get('width')->getValue(),
        'height' => (int) $object->get('height')->getValue(),
        'alt' => $object->get('alt')->getValue(),
        'title' => $object->get('title')->getValue(),
        // @todo find a way to speed up the url generation, this takes more than 30% of the time.
        'url' => file_create_url($object->entity->getFileUri()),
      ];
      return $normalizedValue;
    }

    if ($object instanceof LinkItem) {
      return [
        'url' => $object->get('uri')->getValue(),
        'text' => $object->get('title')->getValue(),
      ];
    }

    if ($object instanceof TextWithSummaryItem) {
      return $object->getValue();
    }

    return parent::normalize($object, $format, $context);
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
