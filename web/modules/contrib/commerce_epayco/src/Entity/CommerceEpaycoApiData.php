<?php

namespace Drupal\commerce_epayco\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Provides an entity to store different settings as needed.
 *
 * @ConfigEntityType(
 *   id = "commerce_epayco_api_data",
 *   label = @Translation("Commerce ePayco API data"),
 *   admin_permission = "administer commerce epayco api data",
 *   handlers = {
 *     "access" = "Drupal\commerce_epayco\CommerceEpaycoApiDataAccessController",
 *     "list_builder" = "Drupal\commerce_epayco\Controller\CommerceEpaycoApiDataListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_epayco\Form\CommerceEpaycoApiDataAddForm",
 *       "edit" = "Drupal\commerce_epayco\Form\CommerceEpaycoApiDataEditForm",
 *       "delete" = "Drupal\commerce_epayco\Form\CommerceEpaycoApiDataDeleteForm"
 *     }
 *   },
 *   config_prefix = "api_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/commerce-epayco/api-data/{commerce_epayco_api_data}/edit",
 *     "delete-form" = "/admin/commerce/config/commerce-epayco/api-data/{commerce_epayco_api_data}/delete"
 *   }
 * )
 */
class CommerceEpaycoApiData extends ConfigEntityBase {

  /**
   * The entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The entity label.
   *
   * @var string
   */
  public $label;

  /**
   * The API Key.
   *
   * @var string
   */
  public $api_key;

  /**
   * The Private Key.
   *
   * @var string
   */
  public $private_key;

  /**
   * The language code.
   *
   * @var string
   */
  public $language;

  /**
   * The p_key value.
   *
   * @var string
   */
  public $p_key;

  /**
   * The p_cust_id_client value.
   *
   * @var string
   */
  public $p_cust_id_client;

  /**
   * Test mode control.
   *
   * @var bool
   */
  public $test;

  /**
   * Get API key.
   */
  public function getApiKey() {
    return $this->api_key;
  }

  /**
   * Get Private key.
   */
  public function getPrivateKey() {
    return $this->private_key;
  }

  /**
   * Get language code.
   */
  public function getLanguageCode() {
    return $this->language;
  }

  /**
   * Get p_key.
   */
  public function getPkey() {
    return $this->p_key;
  }

  /**
   * Get p_cust_id_cliente.
   */
  public function getIdClient() {
    return $this->p_cust_id_client;
  }

  /**
   * Check if test mode.
   */
  public function isTestMode() {
    return $this->test;
  }

}
