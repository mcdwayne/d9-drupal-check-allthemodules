<?php

namespace Drupal\dat;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DAT Adminer plugins.
 */
interface DatAdminerPluginInterface extends PluginInspectionInterface {

  /**
   * Return the name of a plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the description of a plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return the DAT Adminer name of a plugin.
   *
   * @return string
   */
  public function getAdminerName();

  /**
   * Return the DAT Adminer plugin group.
   *
   * @return string
   */
  public function getGroup();

  /**
   * Is adminer allowed plugin.
   *
   * @return bool
   *   True if allowed.
   */
  public function isAdminerAllowed();

  /**
   * Is Editor allowed plugin.
   *
   * @return bool
   *   True if allowed.
   */
  public function isEditorAllowed();

}
