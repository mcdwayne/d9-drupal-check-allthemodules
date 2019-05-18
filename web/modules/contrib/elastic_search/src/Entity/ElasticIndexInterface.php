<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Elastic index entities.
 */
interface ElasticIndexInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getIndexName(): string;

  /**
   * @param string $separator
   */
  public function setSeparator(string $separator);

  /**
   * @return string
   */
  public function getSeparator(): string;

  /**
   * @param string $indexId
   */
  public function setIndexId(string $indexId);

  /**
   * @return string
   */
  public function getIndexId(): string;

  /**
   * @param string $indexLanguage
   */
  public function setIndexLanguage(string $indexLanguage);

  /**
   * @return string
   */
  public function getIndexLanguage(): string;

  /**
   * @return bool
   */
  public function needsUpdate(): bool;

  /**
   * @param bool $needsUpdate
   */
  public function setNeedsUpdate(bool $needsUpdate = TRUE);

  /**
   * @return string
   */
  public function getMappingEntityId(): string;

  /**
   * @param string $mapping
   */
  public function setMappingEntityId(string $mapping);

}
