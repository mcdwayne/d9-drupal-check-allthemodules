<?php

namespace Drupal\entity_pilot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\entity_pilot\AccountInterface;

/**
 * Defines the account entity.
 *
 * @ConfigEntityType(
 *   id = "ep_account",
 *   label = @Translation("Entity pilot account"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\entity_pilot\Form\AccountForm",
 *       "add" = "Drupal\entity_pilot\Form\AccountForm",
 *       "edit" = "Drupal\entity_pilot\Form\AccountForm",
 *       "delete" = "Drupal\entity_pilot\Form\AccountDeleteForm"
 *     },
 *     "list_builder" = "Drupal\entity_pilot\ListBuilders\AccountListBuilder"
 *   },
 *   admin_permission = "administer entity_pilot accounts",
 *   config_prefix = "account",
 *   bundle_of = "ep_departure",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/entity-pilot/accounts/manage/{ep_account}/delete",
 *     "edit-form" = "/admin/structure/entity-pilot/accounts/manage/{ep_account}",
 *     "create-arrival" = "/admin/structure/entity-pilot/arrivals/add/{ep_account}",
 *     "create-departure" = "/admin/structure/entity-pilot/departures/add/{ep_account}"
 *   }
 * )
 */
class Account extends ConfigEntityBundleBase implements AccountInterface {

  /**
   * The account ID.
   *
   * @var string
   */
  public $id;

  /**
   * The account label.
   *
   * @var string
   */
  public $label;

  /**
   * The description of the account type.
   *
   * @var string
   */
  public $description;

  /**
   * The carrier ID of this account.
   *
   * @var string
   */
  public $carrierId;

  /**
   * The private (black box) key of this account.
   *
   * @var string
   */
  public $blackBoxKey;

  /**
   * The secret for encryption/decryption.
   *
   * @var string
   */
  public $secret;

  /**
   * The secret for encryption/decryption.
   *
   * @var string
   */
  public $legacySecret;

  /**
   * {@inheritdoc}
   */
  public function getCarrierId() {
    return $this->carrierId;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlackBoxKey() {
    return $this->blackBoxKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecret($secret) {
    $this->secret = $secret;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegacySecret() {
    return $this->legacySecret;
  }

  /**
   * {@inheritdoc}
   */
  public function setLegacySecret($legacySecret) {
    $this->legacySecret = $legacySecret;
    return $this;
  }

}
