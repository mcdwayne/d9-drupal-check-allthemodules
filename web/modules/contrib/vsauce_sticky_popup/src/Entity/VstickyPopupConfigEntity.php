<?php

namespace Drupal\vsauce_sticky_popup\Entity;

/**
 * @file
 * Contains \Drupal\vsauce_sticky_popup\Entity\VstickyPopupConfigEntity.
 */

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Vsauce Sticky Popup Config entity.
 *
 * @ConfigEntityType(
 *   id= "v_sticky_config_entity",
 *   label = @Translation("Vsauce Sticky Popup Items"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vsauce_sticky_popup\VstickyPopupConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vsauce_sticky_popup\Form\VstickyPopupConfigEntityForm",
 *       "edit" = "Drupal\vsauce_sticky_popup\Form\VstickyPopupConfigEntityForm",
 *       "delete" = "Drupal\vsauce_sticky_popup\Form\VstickyPopupConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *      "html" = "Drupal\vsauce_sticky_popup\VstickyPopupConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "v_sticky_popup_config_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "path_id" = "path_id",
 *     "position_sticky_popup" = "position_sticky_popup",
 *     "position_open_button" = "position_open_button",
 *     "collapsed" = "collapsed",
 *     "position_arrow" = "position_arrow",
 *     "content_type" = "content_type",
 *     "content" = "content",
 *      "tab_label" = "tab_label",
 *   },
 *   links = {
 *      "canonical" = "/admin/structure/vsauce_sticky_popup/entity_config/{v_sticky_config_entity}",
 *      "add-form" = "/admin/structure/vsauce_sticky_popup/entity_config/add",
 *      "edit-form" = "/admin/structure/vsauce_sticky_popup/entity_config/{v_sticky_config_entity}/edit",
 *      "delete-form" = "/admin/structure/vsauce_sticky_popup/entity_config/{v_sticky_config_entity}/delete",
 *      "collection" = "/admin/structure/vsauce_sticky_popup/entity_config"
 *   }
 * )
 */
class VstickyPopupConfigEntity extends ConfigEntityBase implements VstickyPopupConfigEntityInterface {

  protected $id;
  protected $label;
  protected $tab_label;
  protected $path_id;
  protected $position_sticky_popup;
  protected $position_open_button;
  protected $collapsed;
  protected $position_arrow;
  protected $content_type;
  protected $content;

  /**
   * The pathId.
   *
   * @return string|null
   *   string or null by default
   */
  public function pathId() {
    return isset($this->path_id) ? $this->path_id : NULL;
  }

  /**
   * The positionStickyPopup.
   *
   * @return string|null
   *   string or 'default'
   */
  public function positionStickyPopup() {
    return isset($this->position_sticky_popup) ? $this->position_sticky_popup : 'default';
  }

  /**
   * The positionOpenButton.
   *
   * @return string|null
   *   string or 'default'
   */
  public function positionOpenButton() {
    return isset($this->position_open_button) ? $this->position_open_button : 'default';
  }

  /**
   * The collapsed.
   *
   * @return string|null
   *   string or 'default'
   */
  public function collapsed() {
    return isset($this->collapsed) ? $this->collapsed : 1;
  }

  /**
   * The positionArrow.
   *
   * @return string|null
   *   string or 'default'
   */
  public function positionArrow() {
    return isset($this->position_arrow) ? $this->position_arrow : 'default';
  }

  /**
   * The contentType.
   *
   * @return string|null
   *   string or 'text'
   */
  public function contentType() {
    return isset($this->content_type) ? $this->content_type : 'text';
  }

  /**
   * The content.
   *
   * @return string|null
   *   string or null
   */
  public function content() {
    return isset($this->content) ? $this->content : NULL;
  }

  /**
   * The tabLabel.
   *
   * @return string|null
   *   string or null
   */
  public function tabLabel() {
    return isset($this->tab_label) ? $this->tab_label : NULL;
  }

}
