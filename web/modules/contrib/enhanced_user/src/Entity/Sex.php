<?php

namespace Drupal\enhanced_user\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Sex entity.
 *
 * @ConfigEntityType(
 *   id = "sex",
 *   label = @Translation("Sex"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\enhanced_user\SexListBuilder",
 *     "form" = {
 *       "add" = "Drupal\enhanced_user\Form\SexForm",
 *       "edit" = "Drupal\enhanced_user\Form\SexForm",
 *       "delete" = "Drupal\enhanced_user\Form\SexDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\enhanced_user\SexHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "sex",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/people/sex/{sex}",
 *     "add-form" = "/admin/config/people/sex/add",
 *     "edit-form" = "/admin/config/people/sex/{sex}/edit",
 *     "delete-form" = "/admin/config/people/sex/{sex}/delete",
 *     "collection" = "/admin/config/people/sex"
 *   }
 * )
 */
class Sex extends ConfigEntityBase implements SexInterface {

  /**
   * The Sex ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Sex label.
   *
   * @var string
   */
  protected $label;

}
