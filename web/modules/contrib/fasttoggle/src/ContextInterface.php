<?php
/**
 * @file
 * Provides Drupal\fasttoggle\ContextInterface.
 */

namespace Drupal\fasttoggle;

/**
 * An interface for adding contexts in which toggles may be displayed.
 */
interface ContextInterface {

  /**
   * Context ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  public function id();

  /**
   * Context title.
   *
   * @return integer
   *   The short description of the context.
   */
  public function title();

  /**
   * Global settings description.
   *
   * @return string
   *   A description to use in global settings.
   */
  public function globalSettingsDescription();

  /**
   * Return the sitewide admin form controls for this setting.
   *
   * @param $config
   *   Configuration object for the module.
   *
   * @return array
   *   Array of field elements for this setting.
   */
  public function sitewideForm($config);

  /**
   * Modify the form controls for this context.
   *
   * @param $config
   *   Configuration object for the module.
   *
   * @return array
   *   Array of field elements for this setting.
   */
  public function alterForm($config);

}
