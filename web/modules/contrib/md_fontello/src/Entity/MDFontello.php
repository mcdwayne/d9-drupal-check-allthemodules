<?php

namespace Drupal\md_fontello\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the MDFontello entity.
 *
 * @ConfigEntityType(
 *   id = "md_fontello",
 *   label = @Translation("MD Fontello"),
 *   handlers = {
 *     "list_builder" = "Drupal\md_fontello\MDFontelloListBuilder",
 *     "form" = {
 *       "add" = "Drupal\md_fontello\Form\MDFontelloForm",
 *       "delete" = "Drupal\md_fontello\Form\MDFontelloDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\md_fontello\MDFontelloHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "md_fontello",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/md_fontello/{md_fontello}",
 *     "add-form" = "/admin/structure/md_fontello/add",
 *     "delete-form" = "/admin/structure/md_fontello/{md_fontello}/delete",
 *     "collection" = "/admin/structure/md_fontello"
 *   }
 * )
 */
class MDFontello extends ConfigEntityBase implements MDFontelloInterface {

  /**
   * The MDFontello ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The MDFontello label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var string serialize list file of font
   */
  public $files;

  /**
   * @var string serialize list file of classes
   */
  public $classes;
}
