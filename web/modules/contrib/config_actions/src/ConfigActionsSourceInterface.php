<?php

namespace Drupal\config_actions;

/**
 * Defines an interface for config actions plugins
 */
interface ConfigActionsSourceInterface {

  /** ---------------------------------------------- */
  /** ABSTRACT Functions to be implemented in Plugin */
  /** ---------------------------------------------- */

  /**
   * Load data from the source.
   *
   * @return array config data
   */
  public function doLoad();

  /**
   * Save data to the source.
   *
   * @param array $data
   * @return bool TRUE if the data was saved.
   */
  public function doSave($data);

  /**
   * Determine if $source is valid for the specific plugin.
   *
   * @param mixed $source
   * @return bool
   *   TRUE if $source is a valid reference for this plugin.
   */
  public function detect($source);

  /** ------------------------------------------- */
  /** GENERAL Functions implemented in Base class */
  /** ------------------------------------------- */

  /**
   * Load data from the source.
   *
   * @return array Loaded config data.
   */
  public function load();

  /**
   * Save data to the source.
   *
   * @param mixed $data
   * @return bool TRUE if the data was saved.
   */
  public function save($data);

  /**
   * Get the data cached from the last load/save.
   * @return mixed
   */
  public function getData();

  /**
   * Set the data cached in this plugin instance.
   * Causes the plugin to be marked as Changed.
   * @param array $data
   * @param bool $changed
   *   whether to marked the data as changed.
   * @return array
   *   Returns the $data
   */
  public function setData($data = [], $changed = TRUE);

  /**
   * Return TRUE if the data has changed since the last load.
   * @return bool
   */
  public function isChanged();

  /**
   * Return the type of plugin.
   * @return string
   */
  public function getType();

  /**
   * Return whether the data from this source will be merged
   * @return bool
   */
  public function getMerge();

  /**
   * Set whether data saved in this source should be merged with existing data
   * @param bool $merge
   */
  public function setMerge($merge);

}
