<?php

namespace Drupal\bookkeeping\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the account entity.
 *
 * @ConfigEntityType(
 *   id = "bookkeeping_account",
 *   label = @Translation("Account"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bookkeeping\AccountListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bookkeeping\Form\AccountForm",
 *       "edit" = "Drupal\bookkeeping\Form\AccountForm",
 *       "delete" = "Drupal\bookkeeping\Form\AccountDeleteForm"
 *     },
 *     "access" = "Drupal\bookkeeping\AccountAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bookkeeping_account",
 *   admin_permission = "administer bookkeeping",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/bookkeeping/accounts/{bookkeeping_account}",
 *     "add-form" = "/admin/bookkeeping/accounts/add",
 *     "edit-form" = "/admin/bookkeeping/accounts/{bookkeeping_account}/edit",
 *     "delete-form" = "/admin/bookkeeping/accounts/{bookkeeping_account}/delete",
 *     "collection" = "/admin/bookkeeping/accounts"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "rollup",
 *     "code",
 *     "department",
 *   }
 * )
 */
class Account extends ConfigEntityBase implements AccountInterface {

  /**
   * The Account ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Account label.
   *
   * @var string
   */
  protected $label;

  /**
   * The type of account.
   *
   * @var string
   */
  protected $type;

  /**
   * Whether to roll up transactions in exports.
   *
   * @var bool
   */
  protected $rollup = FALSE;

  /**
   * The account code.
   *
   * @var string
   */
  protected $code = '';

  /**
   * The account department.
   *
   * @var string
   */
  protected $department = '';

  /**
   * The account type options.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  private static $typeOptions;

  /**
   * {@inheritdoc}
   */
  public function setLabel(string $label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): ?string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel(): TranslatableMarkup {
    return self::getTypeOptions()[$this->type];
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRollup(): bool {
    return $this->rollup;
  }

  /**
   * {@inheritdoc}
   */
  public function setRollup(bool $rollup) {
    $this->rollup = $rollup;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(): string {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode(string $code) {
    $this->code = $code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepartment(): string {
    return $this->department;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepartment(string $department) {
    $this->department = $department;
    return $this;
  }

  /**
   * Get the options for the account type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The account type labels, keyed by value.
   */
  public static function getTypeOptions(): array {
    // Use static caching for speed.
    if (!isset(self::$typeOptions)) {
      self::$typeOptions = [
        AccountInterface::TYPE_ASSET => new TranslatableMarkup('Asset'),
        AccountInterface::TYPE_LIABILITY => new TranslatableMarkup('Liability'),
        AccountInterface::TYPE_INCOME => new TranslatableMarkup('Income'),
        AccountInterface::TYPE_EXPENSE => new TranslatableMarkup('Expense'),
      ];
    }
    return self::$typeOptions;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var self $a */
    /** @var self $b */
    $type_order = array_flip(array_keys(self::getTypeOptions()));
    $a_weight = $type_order[$a->type];
    $b_weight = $type_order[$b->type];
    if ($a_weight == $b_weight) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
