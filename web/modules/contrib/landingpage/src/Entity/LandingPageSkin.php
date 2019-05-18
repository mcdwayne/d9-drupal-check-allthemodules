<?php

namespace Drupal\landingpage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the LandingPageSkin entity.
 *
 * @ConfigEntityType(
 *   id = "landingpage_skin",
 *   label = @Translation("LandingPageSkin"),
 *   handlers = {
 *     "list_builder" = "Drupal\landingpage\LandingPageSkinListBuilder",
 *     "form" = {
 *       "add" = "Drupal\landingpage\Form\LandingPageSkinForm",
 *       "edit" = "Drupal\landingpage\Form\LandingPageSkinForm",
 *       "delete" = "Drupal\landingpage\Form\LandingPageSkinDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\landingpage\LandingPageSkinHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "landingpage_skin",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/landingpage_skin/{landingpage_skin}",
 *     "add-form" = "/admin/structure/landingpage_skin/add",
 *     "edit-form" = "/admin/structure/landingpage_skin/{landingpage_skin}/edit",
 *     "delete-form" = "/admin/structure/landingpage_skin/{landingpage_skin}/delete",
 *     "collection" = "/admin/structure/landingpage_skin"
 *   }
 * )
 */
class LandingPageSkin extends ConfigEntityBase implements LandingPageSkinInterface {

  /**
   * The LandingPageSkin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The LandingPageSkin label.
   *
   * @var string
   */
  protected $label;

}
