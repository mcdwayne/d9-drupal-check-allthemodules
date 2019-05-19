<?php

/**
 * @file
 * Contains \Drupal\smartling\Normalizer\ContentEntitySmartlingNormalizer.
 */

namespace Drupal\smartling\Normalizer;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class ContentEntitySmartlingNormalizer extends EntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * Format allowed to [de]serialize.
   *
   * @var string
   *
   * @see \Drupal\serialization\Normalizer\NormalizerBase::checkFormat()
   * @see \Drupal\serialization\Normalizer\NormalizerBase::supportsNormalization()
   * @see \Drupal\serialization\Normalizer\NormalizerBase::supportsDenormalization()
   */
  protected $format = 'smartling_xml';

  /**
   * Checks that field type could be normalized.
   *
   * @param string $type
   *   The field type.
   *
   * @return bool
   *   TRUE when field type is allowed to be normalized.
   */
  protected function isFieldTypeAllowed($type) {
    // @todo Make the list configurable.
    return in_array($type, [
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary',
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @see _content_translation_form_language_content_settings_form_alter()
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $context += array(
      'account' => NULL,
    );

    $attributes = [];
    foreach ($entity as $name => $field) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field */
      $definition = $field->getFieldDefinition();
      if ($definition->isTranslatable() && $this->isFieldTypeAllowed($definition->getType())) {
        $attributes[] = [
          '@field_name' => $field->getName(),
          'field_item' => $this->serializer->normalize($entity->get($name), 'xml', $context),
        ];
      }
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $denormalized_data = [];
    foreach ($data as $property => $values) {
      $denormalized_data[$values['@field_name']] = $values['field_item'];
    }

    if (isset($context['bundle_key'])) {
      // @see \Drupal\serialization\Normalizer\EntityNormalizer::denormalize()
      $denormalized_data[$context['bundle_key']] = $context['bundle_value'];
    }

    return parent::denormalize($denormalized_data, $class, $format, $context);
  }

}
