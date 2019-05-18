<?php

namespace Drupal\inherit_link_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Inherit link entity.
 *
 * @ConfigEntityType(
 *   id = "inherit_link",
 *   label = @Translation("Inherit link"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\inherit_link_ui\InheritLinkListBuilder",
 *     "form" = {
 *       "add" = "Drupal\inherit_link_ui\Form\InheritLinkForm",
 *       "edit" = "Drupal\inherit_link_ui\Form\InheritLinkForm",
 *       "delete" = "Drupal\inherit_link_ui\Form\InheritLinkDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\inherit_link_ui\InheritLinkHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "inherit_link",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "element_selector" = "element_selector",
 *     "link_selector" = "link_selector",
 *     "prevent_selector" = "prevent_selector",
 *     "hide_element" = "hide_element",
 *     "auto_external" = "auto_external"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/inherit_link/{inherit_link}",
 *     "add-form" = "/admin/config/inherit_link/add",
 *     "edit-form" = "/admin/config/inherit_link/{inherit_link}/edit",
 *     "delete-form" = "/admin/config/inherit_link/{inherit_link}/delete",
 *     "collection" = "/admin/config/inherit_link"
 *   }
 * )
 */
class InheritLink extends ConfigEntityBase implements InheritLinkInterface {

  /**
   * ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label.
   *
   * @var string
   */
  protected $label;

  /**
   * Element selector.
   *
   * @var string
   */
  protected $element_selector;

  /**
   * Link selector.
   *
   * @var string
   */
  protected $link_selector;

  /**
   * Prevent selector.
   *
   * @var string
   */
  protected $prevent_selector;

  /**
   * Hide element.
   *
   * @var bool
   */
  protected $hide_element;

  /**
   * Auto external.
   *
   * @var bool
   */
  protected $auto_external;

  /**
   * {@inheritdoc}
   */
  public function getElementSelector() {
    return $this->element_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkSelector() {
    return $this->link_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreventSelector() {
    return $this->prevent_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function getHideElement() {
    return $this->hide_element;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoExternal() {
    return $this->auto_external;
  }

  /**
   * Retrieve all attributes.
   *
   * @return array
   *   Attributes.
   */
  public function getAttributes() {
    return [
      "id" => $this->id(),
      "label" => $this->label(),
      "element_selector" => $this->getElementSelector(),
      "link_selector" => $this->getLinkSelector(),
      "prevent_selector" => $this->getPreventSelector(),
      "hide_element" => $this->getHideElement(),
      "auto_external" => $this->getAutoExternal(),
    ];
  }

}
