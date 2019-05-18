<?php

namespace Drupal\gridstack\Entity;

/**
 * Provides an interface defining GridStack entity.
 */
interface GridStackInterface extends GridStackBaseInterface {

  /**
   * Returns the values of gridstack settings.
   *
   * @param bool $merged
   *   The flag indicating to merge values with default ones.
   *
   * @return mixed
   *   The settings values.
   */
  public function getSettings($merged = TRUE);

  /**
   * Sets the values of gridstack settings.
   *
   * @param array $values
   *   The setting values.
   * @param bool $merged
   *   The flag indicating to merge values with default ones.
   *
   * @return $this
   *   The class is being called.
   */
  public function setSettings(array $values, $merged = TRUE);

  /**
   * Returns the value of a gridstack setting.
   *
   * @param string $name
   *   The setting name.
   *
   * @return mixed
   *   The option value.
   */
  public function getSetting($name);

  /**
   * Sets the value of a gridstack setting.
   *
   * @param string $name
   *   The setting name.
   * @param string $value
   *   The setting value.
   *
   * @return $this
   *   The class is being called.
   */
  public function setSetting($name, $value);

  /**
   * Returns options.breakpoints.[lg|xl].[grids|nested].
   *
   * @param string $current
   *   The name of specific property holding grids: grids, or nested.
   *
   * @return mixed|array
   *   Available grids by the given $current parameter, else empty.
   */
  public function getEndBreakpointGrids($current = 'grids');

  /**
   * Returns the current nested grids with preserved indices even if empty.
   *
   * Only cares for the last breakpoint, others inherit its structure.
   * The reason is all breakpoints may have different DOM positionings, heights
   * and widths each, but they must have the same grid structure.
   *
   * @param int $delta
   *   The current delta.
   *
   * @return mixed|array
   *   Available grids by the given $delta parameter, else empty.
   */
  public function getNestedGridsByDelta($delta = 0);

  /**
   * Returns options.breakpoints.[xs|sm|md|lg|xl], or all, else empty.
   *
   * If available, data may contain: column, image_style, width, grids, nested.
   *
   * @param string $breakpoint
   *   The current breakpoint: xs, sm, md, lg, xl.
   *
   * @return array
   *   Available data by the given $breakpoint parameter, else empty.
   */
  public function getBreakpoints($breakpoint = NULL);

  /**
   * Returns regions based on available grids.
   *
   * @param bool $clean
   *   The flag to exclude region containers.
   *
   * @return array
   *   Available regions, else empty.
   */
  public function prepareRegions($clean = TRUE);

}
