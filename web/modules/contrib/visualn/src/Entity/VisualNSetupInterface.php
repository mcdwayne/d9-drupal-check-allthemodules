<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining VisualN Setup entities.
 */
interface VisualNSetupInterface extends ConfigEntityInterface {

  /**
   * Get VisualN setup baker Id.
   *
   * @return string $baker_id
   */
  public function getBakerId();

  /**
   * Get the instance of the VisualNSetup entity baker plugin.
   *
   * @return \Drupal\visualn\Core\SetupBakerBase
   */
  public function getSetupBakerPlugin();

  /**
   * Get VisualN setup baker configuration.
   *
   * @return array $baker_config
   */
  public function getBakerConfig();

  /**
   * Set baker plugin configuration for VisualN setup.
   *
   * @param array $baker_config
   *
   * @return $this
   */
  public function setBakerConfig($baker_config);

}
