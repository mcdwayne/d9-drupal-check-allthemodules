<?php

namespace Drupal\simple_amp;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface AmpComponent plugins.
 */
interface AmpComponentInterface extends PluginInspectionInterface {

  /**
   * Return the name of the AMP Component.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the AMP Component.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return an array of regular expressions for the amp component.
   *
   * @return array regexp
   */
  public function getRegexp();

  /**
   * Return AMP component Javascript.
   *
   * @return url
   */
  public function getElement();

}
