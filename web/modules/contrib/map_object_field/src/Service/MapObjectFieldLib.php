<?php
namespace Drupal\map_object_field\Service;

/**
 * Class MapObjectFieldLib.
 *
 * @package Drupal\map_object_field\Service
 */
class MapObjectFieldLib implements MapObjectLibInterface {

  /**
   * Libraries used by widget.
   */
  public function getLibrariesForWidget() {
    return [
      'map_object_field/map-object-field-default-widget.' . $this->getLibGroup(),
    ];
  }

  /**
   * Libraries used by widget config.
   */
  public function getLibrariesForWidgetConfig() {
    return [
      'map_object_field/map-object-field-default-widget-config.' . $this->getLibGroup(),
    ];
  }

  /**
   * Libraries used by formatter.
   */
  public function getLibrariesForFormatter() {
    return [
      'map_object_field/map-object-field-default-formatter.' . $this->getLibGroup(),
    ];
  }

  /**
   * This will allow to add more map providers.
   *
   * We're going to add more map providers,
   * and allow admin to choose which to use.
   *
   * @return string
   *   Name of map provider.
   */
  protected function getLibGroup() {
    return 'google';
  }

}
