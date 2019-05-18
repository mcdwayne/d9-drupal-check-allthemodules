<?php

namespace Drupal\media_entity_icon;

/**
 * Interface for SvgManager.
 *
 * @package Drupal\media_entity_icon
 */
interface SvgManagerInterface {

  /**
   * Gather width and height from icon.
   *
   * @param string $svg_path
   *   SVG local or distant path.
   * @param int $icon_id
   *   Icon identifier.
   *
   * @return array
   *   Width and height as keys of an array.
   */
  public function getIconSize($svg_path, $icon_id);

  /**
   * Extract icon ids.
   *
   * @param string $svg_path
   *   SVG local or distant path.
   *
   * @return array
   *   Found icons fetched by IDs.
   */
  public function extractIconIds($svg_path);

  /**
   * Extract icon as a single SVG.
   *
   * @param string $svg_path
   *   SVG local or distant path.
   * @param int $icon_id
   *   Icon identifier.
   *
   * @return string
   *   SVG string representing the isolated icon.
   */
  public function extractIconAsSvg($svg_path, $icon_id);

}
