<?php

namespace Drupal\commerce_pricelist_test\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the Widget purchasable entity.
 *
 * @ContentEntityType(
 *   id = "commerce_pricelist_widget",
 *   label = @Translation("Widget"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_test\EntityTestListBuilder",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   base_table = "entity_test",
 *   admin_permission = "administer commerce_pricelist_widget",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/widget/{commerce_pricelist_widget}",
 *     "add-form" = "/widget/add",
 *     "edit-form" = "/widget/manage/{commerce_pricelist_widget}/edit",
 *     "delete-form" = "/widget/manage/{commerce_pricelist_widget}/delete",
 *   },
 *   field_ui_base_route = "entity.entity_test.admin_form",
 * )
 */
class Widget extends EntityTest implements PurchasableEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return NULL;
  }

}
