<?php

namespace Drupal\analytics\AnalyticsService;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for analytics service plugins.
 */
interface AnalyticsServiceInterface extends PluginInspectionInterface {

  /**
   * Returns the machine name of the analytics plugin instance.
   *
   * @return string
   */
  public function getMachineName();

  /**
   * Returns the label of the analytics service.
   *
   * @return string
   *   The label of this analytics service.
   */
  public function getLabel();

  /**
   * Returns the description of the analytics service.
   *
   * @return string
   *   The description of this analytics service.
   */
  public function getDescription();

  /**
   * Returns the type of the plugin instance.
   *
   * @return string
   */
  public function getService();

  /**
   *
   * @return array
   */
  public function getConfiguration();

  /**
   * Determines if the current service can track the current request.
   *
   * @param array $context
   *
   * @return bool
   *   TRUE if the service should output on the current page, otherwise FALSE.
   */
  public function canTrack();

  /**
   * Returns the output of the analytics service.
   *
   * @return array
   *   A structured, renderable array.
   */
  public function getOutput();

  /**
   * @return array
   */
  public function getCacheableUrls();

}
