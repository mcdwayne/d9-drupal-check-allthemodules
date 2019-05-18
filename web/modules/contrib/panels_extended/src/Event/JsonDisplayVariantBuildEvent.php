<?php

namespace Drupal\panels_extended\Event;

use Drupal\panels_extended\Plugin\DisplayVariant\ExtendedPanelsDisplayVariant;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired after building the JSON output.
 */
class JsonDisplayVariantBuildEvent extends Event {

  /**
   * Event name for alter build for JSON output.
   */
  const ALTER_BUILD = 'panels_extended.json_alter_build';

  /**
   * The build array.
   *
   * @var array
   */
  protected $build;

  /**
   * The display that was rendered.
   *
   * @var \Drupal\panels_extended\Plugin\DisplayVariant\ExtendedPanelsDisplayVariant
   */
  protected $display;

  /**
   * Constructor.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\panels_extended\Plugin\DisplayVariant\ExtendedPanelsDisplayVariant $display
   *   The display that was rendered.
   */
  public function __construct(array &$build, ExtendedPanelsDisplayVariant $display) {
    $this->build = &$build;
    $this->display = $display;
  }

  /**
   * Gets the build array as reference.
   *
   * @return array
   *   Reference to the build array.
   */
  public function &getBuild() {
    return $this->build;
  }

  /**
   * Gets the display that was rendered.
   *
   * @return \Drupal\panels_extended\Plugin\DisplayVariant\ExtendedPanelsDisplayVariant
   *   The display that was rendered.
   */
  public function getDisplay() {
    return $this->display;
  }

}
