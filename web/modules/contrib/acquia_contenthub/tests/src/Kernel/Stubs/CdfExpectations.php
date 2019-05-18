<?php

namespace Drupal\Tests\acquia_contenthub\Kernel\Stubs;

/**
 * Class CdfExpectations.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel\Stubs
 */
class CdfExpectations {

  protected $data = [];

  protected $exclusions = [];

  /**
   * Language codes (expected translation variants).
   *
   * @var string[]
   */
  protected $langcodes = [];

  /**
   * Alternative entity loader.
   *
   * The loader can be used as a fallback to load an entity.
   *
   * @var callable
   */
  protected $entityLoader;

  /**
   * CdfExpectations constructor.
   *
   * @param array $data
   *   Data.
   * @param array $exclusions
   *   The list of exclusions (fields).
   */
  public function __construct(array $data, array $exclusions = []) {
    $this->data = $data;
    $this->exclusions = $exclusions;
  }

  /**
   * Return field value.
   *
   * @param string $field_name
   *   Field name.
   * @param string $langcode
   *   Language code.
   *
   * @return mixed|array
   *   Field value.
   */
  public function getFieldValue(string $field_name, string $langcode = NULL) {
    if (is_null($langcode) && isset($this->data[$field_name])) {
      return $this->data[$field_name];
    }

    if (isset($this->data[$field_name][$langcode])) {
      return $this->data[$field_name][$langcode];
    }

    return [];
  }

  /**
   * Checks whether the field is excluded.
   *
   * @param string $field_name
   *   The name of the field to exclude.
   *
   * @return bool
   *   The status of the field:
   *    - TRUE - if the field is excluded;
   *    - FALSE - otherwise.
   */
  public function isExcludedField($field_name) : bool {
    return in_array($field_name, $this->exclusions);
  }

  /**
   * Sets expected supported languages.
   *
   * @param array $langcodes
   *   The list of language codes.
   */
  public function setLangcodes(array $langcodes): void {
    $this->langcodes = $langcodes;
  }

  /**
   * Returns the list of expected language codes.
   *
   * @return string[]
   *   The list of language codes.
   */
  public function getLangcodes(): array {
    return $this->langcodes;
  }

  /**
   * Returns the list of expected field names.
   *
   * @return string[]
   *   The list of field names.
   */
  public function getFieldNames(): array {
    return array_keys($this->data);
  }

  /**
   * Sets alternative entity loader (fallback function).
   *
   * @param callable $entity_loader
   *   Alternative entity loader.
   */
  public function setEntityLoader(callable $entity_loader): void {
    $this->entityLoader = $entity_loader;
  }

  /**
   * Returns alternative entity loader.
   *
   * @return callable
   *   Alternative entity loader.
   */
  public function getEntityLoader(): ?callable {
    return $this->entityLoader;
  }

}
