<?php

namespace Drupal\micro_theme;

/**
 * Interface ManagerAssetInterface.
 */
interface MicroManagerAssetInterface {

  /**
   * {@inheritdoc}
   */
  public function cssInternalFileUri($type, $file_model, $replace_pattern, $site_id);

  /**
   * {@inheritdoc}
   */
  public function cssFilePath($type, $file_model, $replace_pattern, $site_id);

  /**
   * Get the css font file path.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   *
   * @return string
   */
  public function getAsset($type, $site_id);

  /**
   * Get the css font file path.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   *
   * @return string
   */
  public function hasAssetOverride($type, $site_id);

  /**
   * Get a value from the state settings.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   * @param string $key
   *  The key to retrieve.
   *
   * @return mixed
   */
  public function getValue($type, $site_id, $key);

  /**
   * Get the active theme
   *
   * @return string
   *   The theme name.
   */
  public function getActiveTheme();

  /**
   * Are we on the default theme ?
   *
   * @return bool
   */
  public function isDefaultTheme();

}
