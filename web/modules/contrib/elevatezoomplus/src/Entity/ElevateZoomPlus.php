<?php

namespace Drupal\elevatezoomplus\Entity;

use Drupal\slick\Entity\SlickBase;

/**
 * Defines the ElevateZoomPlus configuration entity.
 *
 * @ConfigEntityType(
 *   id = "elevatezoomplus",
 *   label = @Translation("ElevateZoomPlus optionset"),
 *   list_path = "admin/config/media/elevatezoomplus",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "options",
 *   }
 * )
 */
class ElevateZoomPlus extends SlickBase implements ElevateZoomPlusInterface {}
