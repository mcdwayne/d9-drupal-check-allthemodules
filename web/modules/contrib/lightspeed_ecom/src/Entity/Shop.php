<?php

namespace Drupal\lightspeed_ecom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\lightspeed_ecom\ShopInterface;

/**
 * Defines the Lightspeed eCom Shop entity.
 *
 * @ConfigEntityType(
 *   id = "lightspeed_ecom_shop",
 *   label = @Translation("Lightspeed eCom Shop"),
 *   handlers = {
 *     "access" = "Drupal\lightspeed_ecom\ShopAccessControlHandler",
 *     "list_builder" = "Drupal\lightspeed_ecom\ShopEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\lightspeed_ecom\Form\ShopEntityForm",
 *       "edit" = "Drupal\lightspeed_ecom\Form\ShopEntityForm",
 *       "delete" = "Drupal\lightspeed_ecom\Form\ShopEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\lightspeed_ecom\ShopEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "shop",
 *   admin_permission = "administer lightspeed ecom shops",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/lightspeed-ecom/shop/{lightspeed_ecom_shop}",
 *     "add-form" = "/admin/config/services/lightspeed-ecom/shop/add",
 *     "edit-form" = "/admin/config/services/lightspeed-ecom/shop/{lightspeed_ecom_shop}/edit",
 *     "delete-form" = "/admin/config/services/lightspeed-ecom/shop/{lightspeed_ecom_shop}/delete",
 *     "collection" = "/admin/config/services/lightspeed-ecom/shop"
 *   }
 * )
 */
class Shop extends ConfigEntityBase implements ShopInterface {

  /**
   * The Lightspeed eCom Shop ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Lightspeed eCom Shop label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Lightspeed eCom Shop Cluster ID.
   *
   * @var string
   */
  protected $cluster_id;

  /**
   * The Lightspeed eCom Shop API Key.
   *
   * @var string
   */
  protected $api_key;

  /**
   * The Lightspeed eCom Shop API secret.
   *
   * @var string
   */
  protected $api_secret;

  /**
   * {@inheritdoc}
   */
  public function clusterId() {
    return $this->get('cluster_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setClusterId($cluster_id) {
    return $this->set('cluster_id', $cluster_id);
  }

  /**
   * {@inheritdoc}
   */
  public function apiKey() {
    return $this->get('api_key');
  }

  /**
   * {@inheritdoc}
   */
  public function setApiKey($api_key) {
    return $this->set('api_key', $api_key);
  }

  /**
   * {@inheritdoc}
   */
  public function apiSecret() {
    return $this->get('api_secret');
  }

  /**
   * {@inheritdoc}
   */
  public function setApiSecret($api_secret) {
    return $this->set('api_secret', $api_secret);
  }

}
