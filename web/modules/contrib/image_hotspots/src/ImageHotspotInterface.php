<?php

namespace Drupal\image_hotspots;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface ImageHotspotInterface.
 *
 * @ingroup image_hotspots
 *
 * @package Drupal\image_hotspots
 */
interface ImageHotspotInterface extends ContentEntityInterface {

  /**
   * Returns the target image with applied style.
   *
   * @return array
   *   Return array of image style, fid, field name.
   */
  public function getTarget();

  /**
   * Returns the uid of user that created hotspot.
   *
   * @return mixed
   *   Return uuid.
   */
  public function getUid();

  /**
   * Returns title of the hotspot.
   *
   * @return string
   *   Hotspot title.
   */
  public function getTitle();

  /**
   * Sets new title of the hotspot.
   *
   * @param string $title
   *   New title.
   */
  public function setTitle($title);

  /**
   * Returns description of the hotspot.
   *
   * @return string
   *   Hotspot description.
   */
  public function getDescription();

  /**
   * Sets new description of the hotspot.
   *
   * @param string $description
   *   New description.
   */
  public function setDescription($description);

  /**
   * Returns link of the hotspot.
   *
   * @return string
   *   Hotspot link.
   */
  public function getLink();

  /**
   * Sets new link of the hotspot.
   *
   * @param string $url
   *   Url of new link.
   */
  public function setLink($url);

  /**
   * Returns hotspot base coordinates.
   *
   * @return array
   *   Array with X and Y keys for coordinates.
   */
  public function getCoordinates();

  /**
   * Sets new coordinates for hotspot.
   *
   * @param array $coordinates
   *   Array with X and Y keys for new coordinates.
   */
  public function setCoordinates(array $coordinates);

  /**
   * Load all hotspots that referencing to selected fid of field with style.
   *
   * @param array $values
   *   An array with keys: 'field_name', 'fid', 'image_style'.
   *
   * @return array
   *   An array with hotspots.
   */
  public static function loadByTarget(array $values);

}
