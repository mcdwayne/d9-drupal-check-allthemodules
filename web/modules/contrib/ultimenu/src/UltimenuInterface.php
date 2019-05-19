<?php

namespace Drupal\ultimenu;

/**
 * Interface for Ultimenu plugins.
 */
interface UltimenuInterface {

  /**
   * Returns the entity type manager.
   */
  public function getEntityTypeManager();

  /**
   * Returns the block manager.
   */
  public function getBlockManager();

  /**
   * Returns the Config Factory object.
   *
   * @param string $config
   *   The setting storage name.
   *
   * @return class
   *   The \Drupal\Core\Config\ConfigFactoryInterface instance.
   */
  public function getConfig($config = 'ultimenu.settings');

  /**
   * Returns the Ultimenu settings.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return array|null
   *   The settings by its key/ name.
   */
  public function getSetting($setting_name = NULL);

}
