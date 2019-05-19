<?php

namespace Drupal\taxonomy_scheduler\ValueObject;

use Drupal\taxonomy_scheduler\Exception\TaxonomySchedulerException;

/**
 * Class TaxonomyFieldStorageItem.
 */
class TaxonomyFieldStorageItem {

  /**
   * Vocabularies.
   *
   * @var string[]
   */
  private $vocabularies;

  /**
   * FieldLabel.
   *
   * @var string
   */
  private $fieldLabel;

  /**
   * FieldName.
   *
   * @var string
   */
  private $fieldName;

  /**
   * FieldRequired.
   *
   * @var int
   */
  private $fieldRequired;

  /**
   * TaxonomyFieldStorageItem constructor.
   *
   * @param array $data
   *   The data to create the object from.
   */
  public function __construct(array $data) {
    if (!$this->isValid($data)) {
      throw new TaxonomySchedulerException('The given data is not valid for creating a TaxonomyFieldStorageItem');
    }

    $this->vocabularies = $data['vocabularies'];
    $this->fieldLabel = $data['fieldLabel'];
    $this->fieldName = $data['fieldName'];
    $this->fieldRequired = $data['fieldRequired'];
  }

  /**
   * Determines whether the given data array is valid.
   *
   * @param array $data
   *   The data.
   *
   * @return bool
   *   Whether valid or not.
   */
  private function isValid(array $data): bool {
    if (empty($data['vocabularies']) || !\is_array($data['vocabularies'])) {
      return FALSE;
    }

    if (empty($data['fieldLabel'])) {
      return FALSE;
    }

    if (empty($data['fieldName'])) {
      return FALSE;
    }

    if (!\is_numeric($data['fieldRequired'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the vocabularies.
   *
   * @return string[]
   *   The vocabularies array.
   */
  public function getVocabularies(): array {
    return $this->vocabularies;
  }

  /**
   * Gets the field label.
   *
   * @return string
   *   The field label.
   */
  public function getFieldLabel(): string {
    return $this->fieldLabel;
  }

  /**
   * Gets the field name.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName(): string {
    return $this->fieldName;
  }

  /**
   * Gets whether the field is required or not.
   *
   * @return int
   *   The required flag.
   */
  public function getFieldRequired(): int {
    return $this->fieldRequired;
  }

}
