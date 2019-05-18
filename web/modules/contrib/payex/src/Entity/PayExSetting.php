<?php

namespace Drupal\payex\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Configuration for a PayEx integration.
 *
 * @ConfigEntityType(
 *   id = "payex_setting",
 *   label = @Translation("PayEx setting"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\payex\Form\PayExSettingForm",
 *       "delete" = "Drupal\payex\Form\PayExSettingDeleteForm"
 *     },
 *     "list_builder" = "Drupal\payex\PayExSettingListBuilder",
 *   },
 *   config_prefix = "payex_setting",
 *   admin_permission = "administer payex",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/services/payex/{payex_setting}",
 *     "edit-form" = "/admin/config/regional/payex/{payex_setting}",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "defaultCurrencyCode",
 *     "defaultVat",
 *     "encryptionKey",
 *     "live",
 *     "merchantAccount",
 *     "purchaseOperation",
 *     "PPG",
 *   }
 * )
 */
class PayExSetting extends ConfigEntityBase implements PayExSettingInterface {

  /**
   * The ID of the PayEx setting.
   *
   * @var string
   */
  protected $id;

  /**
   * The label for the entity, used in admin UI.
   *
   * @var string
   */
  protected $name;

  /**
   * The default currency code. This is used if no currency code is provided by integration.
   *
   * @var string
   */
  protected $defaultCurrencyCode;

  /**
   * The default vat. This is used if no VAT is provided by integration. Must be submitted as VAT percent * 100.
   * Example: 2500 = 25.00%
   *
   * @var integer
   */
  protected $defaultVat;

  /**
   * The encryption key used as authentication with PayEx APIs.
   *
   * @var string
   */
  protected $encryptionKey;

  /**
   * The live/test status.
   *
   * @var boolean
   */
  protected $live;

  /**
   * The merchant account code.
   *
   * @var string
   */
  protected $merchantAccount;

  /**
   * The Purchase operation.
   *
   * Currently two options are available:
   *  - SALE: Instant capture
   *  - AUTHORIZATION: Authorization only
   */
  protected $purchaseOperation;

  /**
   * The PayEx Payment Gateway (PPG)
   *
   * Currently two versions exist, "1.0" and "2.0"
   *
   * @var string
   */
  protected $PPG;

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrencyCode() {
    return $this->defaultCurrencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultVat() {
    return $this->defaultVat;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionKey() {
    return $this->encryptionKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getLive() {
    return $this->live;
  }

  /**
   * {@inheritdoc}
   */
  public function getMerchantAccount() {
    return $this->merchantAccount;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchaseOperation() {
    return $this->purchaseOperation;
  }

  /**
   * {@inheritdoc}
   */
  public function getPPG() {
    return $this->PPG;
  }

  /**
   * {@inheritdoc}
   */
  public function isLive() {
    return $this->live;
  }

  /**
   * {@inheritdoc}
   */
  public function isTest() {
    return !$this->live;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptionKey($encryptionKey) {
    $this->encryptionKey = $encryptionKey;
    return $this;
  }

}
