<?php

namespace Drupal\webcomponents\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Web component entity.
 *
 * @ConfigEntityType(
 *   id = "web_component",
 *   label = @Translation("Web component entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\webcomponents\WebComponentEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webcomponents\Form\WebComponentEntityForm",
 *       "edit" = "Drupal\webcomponents\Form\WebComponentEntityForm",
 *       "delete" = "Drupal\webcomponents\Form\WebComponentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webcomponents\WebComponentEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "web_component",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/web_component/{web_component}",
 *     "add-form" = "/admin/structure/web_component/add",
 *     "edit-form" = "/admin/structure/web_component/{web_component}/edit",
 *     "delete-form" = "/admin/structure/web_component/{web_component}/delete",
 *     "collection" = "/admin/structure/web_component"
 *   }
 * )
 */
class WebComponentEntity extends ConfigEntityBase implements WebComponentEntityInterface {

  /**
   * The Web component ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Web component label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Web component's file location
   *
   * @var string
   */
  protected $file;

  /**
   * The Web component's properties / attributes
   *
   * @var string[]
   */
  protected $properties;
}
