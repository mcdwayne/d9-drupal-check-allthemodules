<?php

namespace Drupal\frontend\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\frontend\LayoutInterface;

/**
 * Defines the configured layout entity.
 *
 * @ConfigEntityType(
 *   id = "layout",
 *   label = @Translation("Layout"),
 *   label_collection = @Translation("Layouts"),
 *   handlers = {
 *     "list_builder" = "Drupal\frontend\LayoutListBuilder",
 *     "form" = {
 *       "default" = "Drupal\frontend\LayoutForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer layouts",
 *   config_prefix = "layout",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/layout/add",
 *     "delete-form" = "/admin/layout/{layout}/delete",
 *     "canonical" = "/admin/layout/{layout}",
 *     "edit-form" = "/admin/layout/{layout}/edit",
 *     "collection" = "/admin/layout",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "components",
 *   },
 * )
 */
class Layout extends Container implements LayoutInterface {

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('frontend.layout.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

}
