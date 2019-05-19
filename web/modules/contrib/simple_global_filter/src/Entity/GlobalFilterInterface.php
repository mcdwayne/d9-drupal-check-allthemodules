<?php

namespace Drupal\simple_global_filter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Global filter entities.
 */
interface GlobalFilterInterface extends ConfigEntityInterface {

  /**
   * @return: Taxonomy vocabulary name (string)
   */
  public function getVocabulary();

  /**
   * Sets internal vocabulary name
   */
  public function setVocabulary($vocabulary_name);

  /**
   * Returns the default value, used when not any value has been set.
   */
  public function getDefaultValue();

  /**
   * Returns the field used for storing the alias information of the global filter value.
   */
  public function getAliasField();
}
