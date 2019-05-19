<?php

namespace Drupal\html_head_meta_and_link\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Html head meta and link entity entity.
 *
 * @ConfigEntityType(
 *   id = "html_head_meta_and_link_entity",
 *   label = @Translation("Html head meta and link entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\html_head_meta_and_link\HtmlHeadMetaAndLinkEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\html_head_meta_and_link\Form\HtmlHeadMetaAndLinkEntityForm",
 *       "edit" = "Drupal\html_head_meta_and_link\Form\HtmlHeadMetaAndLinkEntityForm",
 *       "delete" = "Drupal\html_head_meta_and_link\Form\HtmlHeadMetaAndLinkEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\html_head_meta_and_link\HtmlHeadMetaAndLinkEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "html_head_meta_and_link_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/html_head_meta_and_link_entity/{html_head_meta_and_link_entity}",
 *     "add-form" = "/admin/config/search/html_head_meta_and_link_entity/add",
 *     "edit-form" = "/admin/config/search/html_head_meta_and_link_entity/{html_head_meta_and_link_entity}/edit",
 *     "delete-form" = "/admin/config/search/html_head_meta_and_link_entity/{html_head_meta_and_link_entity}/delete",
 *     "collection" = "/admin/config/search/html_head_meta_and_link_entity"
 *   }
 * )
 */
class HtmlHeadMetaAndLinkEntity extends ConfigEntityBase implements HtmlHeadMetaAndLinkEntityInterface {

  /**
   * The Html head meta and link entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Html head meta and link entity label.
   *
   * @var string
   */
  protected $label;

}
