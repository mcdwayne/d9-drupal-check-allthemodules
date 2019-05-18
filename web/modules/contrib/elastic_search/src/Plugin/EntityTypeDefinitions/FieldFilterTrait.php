<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 29.01.17
 * Time: 18:20
 */

namespace Drupal\elastic_search\Plugin\EntityTypeDefinitions;

/**
 * Class FieldDefinitionFilterTrait
 *
 * @package Drupal\elastic_search\Plugin\EntityTypeDefinitions
 */
/**
 * Class FieldFilterTrait
 *
 * @package Drupal\elastic_search\Plugin\EntityTypeDefinitions
 */
trait FieldFilterTrait {

  /**
   * @var string
   */
  protected $isRegexRegex = "/^\/[\s\S]+\/$/";

  /**
   * @param string $entityType
   * @param string $bundleType
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions(string $entityType, string $bundleType) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entityType,
                                                             $bundleType);
    return $this->filter($fields);
  }

  /**
   * @param string $isRegexRegex
   */
  public function setIsRegexRegex(string $isRegexRegex) {
    $this->isRegexRegex = $isRegexRegex;
  }

  /**
   * @return array
   */
  abstract protected function allowedFields(): array;

  /**
   * @param array $fields
   *
   * @return array
   */
  protected function filter(array $fields): array {
    $allowed = $this->allowedFields();
    $regex = $this->filterRegexKeys($allowed);

    $filtered = $this->getFilterFields($fields, $allowed);
    $regexed = $this->getRegexFields($fields, $regex);

    return array_merge($filtered, $regexed);
  }

  /**
   * @param array $allowed
   *
   * @return array
   */
  private function filterRegexKeys(array &$allowed) {
    $regex = [];
    foreach ($allowed as $key => $item) {
      if ($this->isRegex($item)) {
        $regex[] = $item;
        unset($allowed[$key]);
      }
    }
    return $regex;
  }

  /**
   * @param array $fields
   * @param array $allowed
   *
   * @return array
   */
  private function getFilterFields(array $fields, array $allowed) {
    return array_filter($fields,
      function ($key) use ($allowed) {
        return in_array($key, $allowed, TRUE);
      },
                        ARRAY_FILTER_USE_KEY);
  }

  /**
   * @param array $fields
   * @param array $regex
   *
   * @return array
   */
  private function getRegexFields(array $fields, array $regex) {

    return array_filter($fields,
      function ($key) use ($regex) {
        foreach ($regex as $item) {
          if (preg_match($item, $key)) {
            return TRUE;
          }
        }
        return FALSE;
      },
                        ARRAY_FILTER_USE_KEY);

  }

  /**
   * @param mixed $str
   *
   * @return int
   */
  private function isRegex($str) {
    return preg_match($this->isRegexRegex, $str);
  }

}