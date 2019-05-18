<?php

namespace Drupal\bibcite_entity\Normalizer;

use Drupal\bibcite_entity\Entity\Contributor;
use Drupal\bibcite_entity\Entity\Keyword;
use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * Base normalizer class for bibcite formats.
 */
abstract class ReferenceNormalizerBase extends EntityNormalizer {

  /**
   * Default reference type. Will be assigned for types without mapping.
   *
   * @var string
   */
  const DEFAULT_REF_TYPE = 'miscellaneous';

  /**
   * The format that this Normalizer supports.
   *
   * @var string
   */
  protected $format;

  /**
   * Format setter for DI calls.
   *
   * @param string|array $format
   *   Normalizer format.
   */
  public function setFormat($format) {
    $this->format = $format;

    foreach ((array) $this->format as $format) {
      $config_name = sprintf('bibcite_entity.mapping.%s', $format);
      $config = $this->configFactory->get($config_name);

      $this->fieldsMapping[$format] = $config->get('fields');
      $this->typesMapping[$format] = $config->get('types');
    }
  }

  /**
   * Default publication type. Will be assigned for types without mapping.
   *
   * @var string
   */
  public $defaultType;

  /**
   * Mapping between bibcite_entity and format publication types.
   *
   * @var array
   */
  protected $typesMapping;

  /**
   * Mapping between bibcite_entity and format fields.
   *
   * @var array
   */
  protected $fieldsMapping;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\bibcite_entity\Entity\ReferenceInterface'];

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Format contributor key.
   *
   * @var string|null
   */
  public $contributorKey = NULL;

  /**
   * Format keyword key.
   *
   * @var null|string
   */
  public $keywordKey = NULL;

  /**
   * Format type key.
   *
   * Default value is 'type'.
   *
   * @var null|string
   */
  public $typeKey = 'type';

  /**
   * Construct new BibliographyNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_manager);

    $this->configFactory = $config_factory;
  }

  /**
   * Get format contributor key.
   *
   * @return string|null
   *   Contributor key.
   */
  protected function getContributorKey() {
    return $this->contributorKey;
  }

  /**
   * Get format keyword key.
   *
   * @return string|null
   *   Keyword key.
   */
  protected function getKeywordKey() {
    return $this->keywordKey;
  }

  /**
   * Get format type key.
   *
   * @return string|null
   *   Type key.
   */
  protected function getTypeKey() {
    return $this->typeKey;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $contributor_key = $this->getContributorKey();
    if (!empty($data[$contributor_key])) {
      $contributors = (array) $data[$contributor_key];
      unset($data[$contributor_key]);
    }

    $keyword_key = $this->getKeywordKey();
    if (!empty($data[$keyword_key])) {
      $keywords = (array) $data[$keyword_key];
      unset($data[$keyword_key]);
    }

    $type_key = $this->getTypeKey();
    if (!$data[$type_key]) {
      throw new \Exception('Incorrect type of reference or not set.');
    }
    $converted_type = $this->convertFormatType($data[$type_key], $format);
    if (!$converted_type) {
      $link = Url::fromRoute('bibcite_entity.mapping', ['bibcite_format' => $format])
        ->toString();
      throw new \Exception(t('@data_type type is not mapped to reference type. <a href = ":url" > Check mapping. </a >', [
        '@data_type' => $data[$type_key],
        ':url' => $link,
      ]));
    }
    unset($data[$type_key]);
    $data = $this->convertKeys($data, $format);
    $data['type'] = $converted_type;
    /* @var \Drupal\bibcite_entity\Entity\Reference $entity */
    $entity = parent::denormalize($data, $class, $format, $context);

    if (!empty($contributors)) {
      $author_field = $entity->get('author');
      foreach ($contributors as $name) {
        $author_field->appendItem($this->serializer->denormalize(['name' => [['value' => $name]]], Contributor::class, $format, $context));
      }
    }
    if (!empty($keywords)) {
      $keyword_field = $entity->get('keywords');
      foreach ($keywords as $keyword) {
        $keyword_field->appendItem($this->serializer->denormalize(['name' => [['value' => $keyword]]], Keyword::class, $format, $context));
      }
    }
    return $entity;
  }

  /**
   * Convert publication type to format type.
   *
   * @param string $type
   *   Bibcite entity publication type.
   * @param string $format
   *   Serializer format.
   *
   * @return string
   *   Format publication type.
   */
  protected function convertEntityType($type, $format) {
    $types_mapping = array_flip(array_filter($this->typesMapping[$format]));
    return isset($types_mapping[$type]) ? $types_mapping[$type] : $this->defaultType;
  }

  /**
   * Convert format type to publication type.
   *
   * @param string $type
   *   Format publication type.
   * @param string $format
   *   Serializer format.
   *
   * @return null|string
   *   Bibcite entity publication type.
   */
  protected function convertFormatType($type, $format) {
    if (isset($this->typesMapping[$format][$type])) {
      return $this->typesMapping[$format][$type];
    }
    elseif (isset($this->typesMapping[$format][$this->defaultType])) {
      return $this->typesMapping[$format][$this->defaultType];
    }
    return self::DEFAULT_REF_TYPE;
  }

  /**
   * Extract fields values from reference entity.
   *
   * @param \Drupal\bibcite_entity\Entity\ReferenceInterface $reference
   *   Reference entity object.
   * @param string $format
   *   Serializer format.
   *
   * @return array
   *   Array of entity values.
   */
  protected function extractFields(ReferenceInterface $reference, $format) {
    $attributes = [];

    foreach ($this->fieldsMapping[$format] as $format_field => $entity_field) {
      if ($entity_field && $reference->hasField($entity_field) && ($field = $reference->get($entity_field)) && !$field->isEmpty()) {
        $attributes[$format_field] = $this->extractScalar($field);
      }
    }

    return $attributes;
  }

  /**
   * Extract keywords labels from field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list
   *   List of field items.
   *
   * @return array
   *   Keywords labels.
   */
  protected function extractKeywords(FieldItemListInterface $field_item_list) {
    $keywords = [];

    foreach ($field_item_list as $field) {
      $keywords[] = $field->entity->label();
    }

    return $keywords;
  }

  /**
   * Extract authors values from field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list
   *   List of field items.
   *
   * @return array
   *   Authors in BibTeX format.
   */
  protected function extractAuthors(FieldItemListInterface $field_item_list) {
    $authors = [];

    foreach ($field_item_list as $field) {
      $authors[] = $field->entity->getName();
    }

    return $authors;
  }

  /**
   * Extract scalar value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $scalar_field
   *   Scalar items list.
   *
   * @return mixed
   *   Scalar value.
   */
  protected function extractScalar(FieldItemListInterface $scalar_field) {
    return $scalar_field->value;
  }

  /**
   * Convert format keys to Bibcite entity keys and filter non-mapped.
   *
   * @param array $data
   *   Array of decoded values.
   * @param string $format
   *   Serializer format.
   *
   * @return array
   *   Array of decoded values with converted keys.
   */
  protected function convertKeys(array $data, $format) {
    $converted = [];

    foreach ($data as $key => $field) {
      if (!empty($this->fieldsMapping[$format][$key])) {
        $converted_key = empty($this->fieldsMapping[$format][$key]) ? $key : $this->fieldsMapping[$format][$key];
        $converted[$converted_key] = [$field];
      }
    }

    return $converted;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($reference, $format = NULL, array $context = []) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface $reference */

    $attributes = [];
    $attributes[$this->typeKey] = $this->convertEntityType($reference->bundle(), $format);

    if ($keywords = $this->extractKeywords($reference->get('keywords'))) {
      $attributes[$this->keywordKey] = $keywords;
    }

    if ($authors = $this->extractAuthors($reference->get('author'))) {
      $attributes[$this->contributorKey] = $authors;
    }

    $attributes += $this->extractFields($reference, $format);

    return $attributes;
  }

}
