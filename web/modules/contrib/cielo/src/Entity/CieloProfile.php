<?php

namespace Drupal\cielo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Cielo profile entity.
 *
 * @ConfigEntityType(
 *   id = "cielo_profile",
 *   label = @Translation("Cielo profile"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cielo\CieloProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\cielo\Form\CieloProfileForm",
 *       "edit" = "Drupal\cielo\Form\CieloProfileForm",
 *       "delete" = "Drupal\cielo\Form\CieloProfileDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cielo\CieloProfileHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "cielo_profile",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/cielo_profile/{cielo_profile}",
 *     "add-form" = "/admin/config/cielo_profile/add",
 *     "edit-form" = "/admin/config/cielo_profile/{cielo_profile}/edit",
 *     "delete-form" = "/admin/config/cielo_profile/{cielo_profile}/delete",
 *     "collection" = "/admin/config/cielo_profile"
 *   }
 * )
 */
class CieloProfile extends ConfigEntityBase implements CieloProfileInterface {

  /**
   * The Cielo profile ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Cielo profile label.
   *
   * @var string
   */
  protected $label;

  /**
   * The merchant id.
   *
   * @var string
   */
  protected $merchant_id;

  /**
   * The merchant key.
   *
   * @var string
   */
  protected $merchant_key;

  /**
   * The environment, production|sandbox.
   *
   * @var string
   */
  protected $environment;

  /**
   * Flag to save or not the transaction log.
   *
   * @var bool
   */
  protected $save_transaction_log;

  /**
   * Get the merchant_id value.
   *
   * @return string
   *   The merchant_id value.
   */
  public function getMerchantId() {
    return $this->merchant_id;
  }

  /**
   * Set the merchant_id value.
   *
   * @param string $merchant_id
   *   The merchant_id.
   */
  public function setMerchantId($merchant_id) {
    $this->merchant_id = $merchant_id;
  }

  /**
   * Get the merchant_key value.
   *
   * @return string
   *   The merchant_key value.
   */
  public function getMerchantKey() {
    return $this->merchant_key;
  }

  /**
   * Set the merchant_key value.
   *
   * @param string $merchant_key
   *   The merchant_key.
   */
  public function setMerchantKey($merchant_key) {
    $this->merchant_key = $merchant_key;
  }

  /**
   * Get the environment value.
   *
   * @return string
   *   The environment value.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Set the environment value.
   *
   * @param string $environment
   *   The environment.
   */
  public function setEnvironment($environment) {
    $this->environment = $environment;
  }

  /**
   * Get the save_transaction_log value.
   *
   * @return bool
   *   The save_transaction_log value.
   */
  public function isSaveTransactionLog() {
    return $this->save_transaction_log;
  }

  /**
   * Set the save_transaction_log value.
   *
   * @param bool $save_transaction_log
   *   The save_transaction_log.
   */
  public function setSaveTransactionLog($save_transaction_log) {
    $this->save_transaction_log = $save_transaction_log;
  }

}
