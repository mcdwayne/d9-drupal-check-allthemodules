<?php

namespace Drupal\content_locker;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the required interface for all content locker plugins.
 *
 * @ingroup content_locker
 */
interface ContentLockerPluginInterface extends PluginInspectionInterface {

  /**
   * Provide a settings form of the content locker.
   */
  public function settingsForm();

  /**
   * Provide default plugin library.
   */
  public function defaultLibrary();

  /**
   * Provide default plugin settings.
   */
  public function defaultSettings();

  /**
   * Additional access validation step.
   */
  public function defaultAccess();

}
