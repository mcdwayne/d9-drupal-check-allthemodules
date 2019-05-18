<?php

namespace Drupal\eloqua_app_cloud\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Eloqua AppCloud Menu Responder plugins.
 */
interface EloquaAppCloudMenuResponderInterface extends PluginInspectionInterface {

  /**
   * Method that gets executed when a Menu Service is invoked from within the
   * Eloqua UI. If additional information is to be displayed, you can add it to
   * the $render array provided via argument.
   *
   * @param array $render
   *   The render array (of the entity, technically) to be displayed to the user
   *   after clicking through the menu service from Eloqua.
   *
   * @param array $params
   *   An associative array of query params that came through on the request.
   *   If you passed in parameters via templated URL from Eloqua (e.g. an
   *   assetType or assetId), this is where they will be.
   *
   * @return null
   */
  public function execute(array &$render, array $params);

}
