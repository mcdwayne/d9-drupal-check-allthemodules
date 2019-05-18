<?php

namespace Drupal\pathalias_extend;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Pathalias Extend Suffix entity.
 */
interface PathaliasExtendSuffixInterface extends ConfigEntityInterface {

  /**
   * Gets target entity type id.
   *
   * @return string
   *   Target entity type id.
   */
  public function getTargetEntityTypeId(): string;

  /**
   * Sets target entity type id.
   *
   * @param string $entity_type_id
   *   Target entity type id.
   */
  public function setTargetEntityTypeId(string $entity_type_id);

  /**
   * Gets target bundle id.
   *
   * @return string
   *   Target bundle id.
   */
  public function getTargetBundleId(): string;

  /**
   * Sets target bundle id.
   *
   * @param string $bundle_id
   *   Target bundle id.
   */
  public function setTargetBundleId(string $bundle_id);

  /**
   * Gets pattern.
   *
   * @return string
   *   Pattern.
   */
  public function getPattern(): string;

  /**
   * Sets pattern.
   *
   * @param string $pattern
   *   Pattern.
   */
  public function setPattern(string $pattern);

  /**
   * Gets create alias.
   *
   * @return bool
   *   Whether to create missing aliases.
   */
  public function getCreateAlias(): bool;

  /**
   * Sets create alias.
   *
   * @param bool $create_alias
   *   Whether to create missing aliases.
   */
  public function setCreateAlias(bool $create_alias);

}
