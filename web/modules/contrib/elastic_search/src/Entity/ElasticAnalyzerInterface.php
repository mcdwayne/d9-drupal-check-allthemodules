<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Elastic analyzer entities.
 */
interface ElasticAnalyzerInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getAnalyzer(): string;

  /**
   * @param string $analyzer
   *
   * @return mixed
   */
  public function setAnalyzer(string $analyzer);

  /**
   * @return bool
   */
  public function isInternal(): bool;

  /**
   * @param bool $internal
   */
  public function setInternal(bool $internal);

}
