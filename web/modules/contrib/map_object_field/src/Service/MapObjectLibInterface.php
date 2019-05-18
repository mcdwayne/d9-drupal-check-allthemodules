<?php
namespace Drupal\map_object_field\Service;
/**
 * Interface for map object libraries.
 */
interface MapObjectLibInterface {

  /**
   * Method should return array of libraries used by widget.
   */
  public function getLibrariesForWidget();

  /**
   * Method should return array of libraries used by widget config.
   */
  public function getLibrariesForWidgetConfig();

  /**
   * Method should return array of libraries used by formatter.
   */
  public function getLibrariesForFormatter();

}
