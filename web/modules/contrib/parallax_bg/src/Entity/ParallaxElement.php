<?php

namespace Drupal\parallax_bg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Parallax element entity.
 *
 * @ConfigEntityType(
 *   id = "parallax_element",
 *   label = @Translation("Parallax element"),
 *   handlers = {
 *     "list_builder" = "Drupal\parallax_bg\ParallaxElementListBuilder",
 *     "form" = {
 *       "add" = "Drupal\parallax_bg\Form\ParallaxElementForm",
 *       "edit" = "Drupal\parallax_bg\Form\ParallaxElementForm",
 *       "delete" = "Drupal\parallax_bg\Form\ParallaxElementDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\parallax_bg\ParallaxElementHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "parallax_element",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "selector",
 *     "position" = "position",
 *     "speed" = "speed",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/parallax_element/{parallax_element}",
 *     "add-form" = "/admin/structure/parallax_element/add",
 *     "edit-form" = "/admin/structure/parallax_element/{parallax_element}/edit",
 *     "delete-form" = "/admin/structure/parallax_element/{parallax_element}/delete",
 *     "collection" = "/admin/structure/parallax_element"
 *   }
 * )
 */
class ParallaxElement extends ConfigEntityBase implements ParallaxElementInterface {

  /**
   * The Parallax element ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * Position.
   *
   * @var string
   */
  protected $position;

  /**
   * Relative speed.
   *
   * @var string
   */
  protected $speed;

  /**
   * @return string
   */
  public function getSelector() {
    return $this->selector;
  }

  /**
   * @return string
   */
  public function getPosition() {
    return $this->position;
  }

  /**
   * @return string
   */
  public function getSpeed() {
    return $this->speed;
  }

}
