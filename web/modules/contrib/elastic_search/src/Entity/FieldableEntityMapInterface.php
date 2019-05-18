<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\elastic_search\ValueObject\IdDetails;

/**
 * Provides an interface for defining Fieldable entity map entities.
 */
interface FieldableEntityMapInterface extends ConfigEntityInterface {

  /**
   * @param string $entity
   * @param string $bundle
   *
   * @return string
   */
  public static function getMachineName(string $entity, string $bundle): string;

  /**
   * @param string $entityId
   *
   * @return array keyed with entity and bundle
   */
  public static function getEntityAndBundle(string $entityId): array;

  /**
   * @return string
   */
  public function getId(): string;

  /**
   * @param string $id
   */
  public function setId(string $id);

  /**
   * @return \Drupal\elastic_search\ValueObject\IdDetails
   */
  public function getIdDetails(): IdDetails;

  /**
   * @return string
   */
  public function getLabel(): string;

  /**
   * @param string $label
   */
  public function setLabel(string $label);

  /**
   * @return bool
   */
  public function isActive(): bool;

  /**
   * @param bool $active
   */
  public function setActive(bool $active);

  /**
   * @return \mixed[]
   */
  public function getFields(): array;

  /**
   * @param \mixed[] $fields
   */
  public function setFields(array $fields);

  /**
   * @return bool
   */
  public function isChildOnly(): bool;

  /**
   * @param bool $childOnly
   */
  public function setChildOnly(bool $childOnly);

  /**
   * @return bool
   */
  public function isSimpleReference(): bool;

  /**
   * @param bool $state
   *
   * @return mixed
   */
  public function setSimpleReference(bool $state = TRUE);

  /**
   * @return bool
   */
  public function hasDynamicMapping(): bool;

  /**
   * @param bool $state
   *
   * @return mixed
   */
  public function setDynamicMapping(bool $state = TRUE);

  /**
   * @param int $depth
   *
   * @return mixed
   */
  public function setRecursionDepth(int $depth);

  /**
   * @return int
   */
  public function getRecursionDepth(): int;

}
