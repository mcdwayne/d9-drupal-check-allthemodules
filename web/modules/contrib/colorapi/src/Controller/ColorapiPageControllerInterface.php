<?php

namespace Drupal\colorapi\Controller;

/**
 * Interface for the Color API Page Controller.
 *
 * All methods in this interface must return either a render array, or a class
 * that extends \Symfony\Component\HttpFoundation\Response.
 *
 * @see \Symfony\Component\HttpFoundation\Response
 */
interface ColorapiPageControllerInterface {

  /**
   * Defines the Color API module settings page.
   *
   * Callback for route: colorapi.color_settings.
   */
  public function moduleSettingsPage();

}
