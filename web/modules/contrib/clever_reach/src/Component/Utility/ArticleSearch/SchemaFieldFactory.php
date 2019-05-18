<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch;

use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\BooleanType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\CommentType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\DateRangeType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\DateType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\ImageType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\LanguageType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\LinkType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\ListType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\NumberType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\StringType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\TaxonomyType;
use Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\TextWithSummaryType;

/**
 * Creates CleverReach instance of schema field based on provided Drupal field.
 */
class SchemaFieldFactory {
  /**
   * List of excluded field codes.
   */
  const EXCLUDED_FIELD_CODES = [
    'nid',
    'vid',
    'uid',
    'type',
    'uuid',
    'created',
  ];

  /**
   * List of supported drupal field types.
   */
  const SUPPORTED_DRUPAL_FIELD_TYEPS = [
    'language' => LanguageType::class,
    'boolean' => BooleanType::class,
    'email' => StringType::class,
    'string' => StringType::class,
    'decimal' => NumberType::class,
    'float' => NumberType::class,
    'integer' => NumberType::class,
    'text_long' => StringType::class,
    'string_long' => StringType::class,
    'created' => DateType::class,
    'changed' => DateType::class,
    'datetime' => DateType::class,
    'timestamp' => DateType::class,
    'text_with_summary' => TextWithSummaryType::class,
    'link' => LinkType::class,
    'list_float' => ListType::class,
    'list_integer' => ListType::class,
    'list_string' => ListType::class,
    'comment' => CommentType::class,
    'daterange' => DateRangeType::class,
    'image@file' => ImageType::class,
    'entity_reference@taxonomy_term' => TaxonomyType::class,
  ];

  /**
   * Gets instance of field configuration type.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|null $field
   *   Field instance.
   *
   * @return \Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType\BaseField|null
   *   If null is returned, field is not supported.
   *   Otherwise returns instance of field.
   */
  public static function getField($field) {
    if (self::isExcludedField($field) || !self::isFieldTypeSupported($field)) {
      return NULL;
    }

    $class = self::SUPPORTED_DRUPAL_FIELD_TYEPS[self::getFieldType($field)];

    return new $class($field);
  }

  /**
   * Checks if field is excluded.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|null $field
   *   Field instance.
   *
   * @return bool
   *   If excluded, returns true, otherwise false.
   */
  private static function isExcludedField($field) {
    return in_array($field->getName(), self::EXCLUDED_FIELD_CODES, TRUE);
  }

  /**
   * Checks if field is supported by module.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|null $field
   *   Field instance.
   *
   * @return bool
   *   If excluded, returns true, otherwise false.
   */
  private static function isFieldTypeSupported($field) {
    return array_key_exists(self::getFieldType($field), self::SUPPORTED_DRUPAL_FIELD_TYEPS);
  }

  /**
   * Returns field type code.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|null $field
   *   Field instance.
   *
   * @return string
   *   Returns field type code.
   */
  private static function getFieldType($field) {
    $fieldType = $field->getType();

    if ($reference = (string) $field->getFieldStorageDefinition()->getSetting('target_type')) {
      $fieldType = "$fieldType@$reference";
    }

    return $fieldType;
  }

}
