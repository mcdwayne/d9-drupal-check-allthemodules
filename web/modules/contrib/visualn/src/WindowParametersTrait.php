<?php

namespace Drupal\visualn;

/**
 * Methods adding drawing window parameters support.
 *
 * The methods are used in various classes involved into drawing rendering pipeline
 * to pass drawing window parameters down the line or obtain them to optionally use
 * to prepare drawing build itself.
 *
 * Usually window parameters are used when rendering embedded or in some way
 * inserted (e.g. as tokens or iframes) drawings into content. Drawing window
 * parameters may be considered by respective drawer or fetcher plugins to
 * generate appropriate markup e.g. to comply with dimensions of the window.
 *
 * The plugins themselves may or may not use these parameters when
 * preparing drawing build, it depends on each single plugin specifics.
 *
 * @see \Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer\LeafletMapBasicDrawer::prepareBuild()
 *
 * @ingroup ckeditor_integration
 */
trait WindowParametersTrait {

  // @todo: implement methods for specific window parameters:
  //   getWindowParameter($parameter)
  //   setWindowParameter($parameter, $value)

  /**
   * The array of drawing window paraters values.
   *
   * @todo: describe window_parameters allowed/expected values and structure
   *
   * @var array
   *   Possible values are:
   *   - width: The width of the drawing window.
   *   - height: The height of the drawing window.
   */
  protected $window_parameters = [];

  /**
   * Get drawing window parameters.
   *
   * @return array
   *   An array of window parameters for the current drawing.
   */
  public function getWindowParameters() {
    return $this->window_parameters;
  }

  /**
   * Set drawing window parameters.
   *
   * @param array $window_parameters
   *   An array of window parameters for the current drawing.
   *
   * @return $this
   */
  public function setWindowParameters(array $window_parameters) {
    $this->window_parameters = $window_parameters;
    return $this;
  }

  // @todo: implement validateWindowParameters() ?
  //   use TypedData Api for window_parameters and validation

  /**
   * Clean window_parameters values.
   */
  public function cleanWindowParameters() {

    if (!empty($this->window_parameters) && is_array($this->window_parameters)) {
      $allowed = ['width', 'height'];
      $window_parameters = array_intersect_key($this->window_parameters, array_flip($allowed));
      $window_parameters = array_filter($window_parameters);
      foreach ($window_parameters as $k => $value) {
        $new_value = (int) $value;
        if ($new_value >= 0 && $new_value <= 10000) {
          $window_parameters[$k] = $new_value;
        }
        else {
          unset($window_parameters[$k]);
        }
      }
    }
    else {
      $window_parameters = [];
    }

    $this->window_parameters = $window_parameters;
  }

}
