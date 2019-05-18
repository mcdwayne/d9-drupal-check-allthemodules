<?php

namespace Drupal\bookkeeping\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an interface for defining accounts.
 */
interface AccountInterface extends ConfigEntityInterface {

  /**
   * An asset account.
   */
  const TYPE_ASSET = 'asset';

  /**
   * A liability account.
   */
  const TYPE_LIABILITY = 'liability';

  /**
   * An income account.
   */
  const TYPE_INCOME = 'income';

  /**
   * An expense account.
   */
  const TYPE_EXPENSE = 'expense';

  /**
   * Set the account label.
   *
   * @param string $label
   *   The account label.
   *
   * @return $this
   */
  public function setLabel(string $label);

  /**
   * Get the account type.
   *
   * @return string
   *   The account type. One of the AccountInterface::TYPE_* constants.
   */
  public function getType(): ?string;

  /**
   * Get the account type label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translatable label for the account type.
   */
  public function getTypeLabel(): TranslatableMarkup;

  /**
   * Set the account type.
   *
   * @param string $type
   *   The account type. One of the AccountInterface::TYPE_* constants.
   *
   * @return $this
   */
  public function setType(string $type);

  /**
   * Check whether this account should roll up transactions in exports.
   *
   * @return bool
   *   Whether this account should roll up transactions in exports.
   */
  public function shouldRollup(): bool;

  /**
   * Set whether this account should roll up transactions in exports.
   *
   * @param bool $rollUp
   *   Whether this account should roll up transactions in exports.
   *
   * @return $this
   */
  public function setRollup(bool $rollUp);

  /**
   * Get the account code.
   *
   * @return string
   *   The account code.
   */
  public function getCode(): string;

  /**
   * Set the account code.
   *
   * @param string $code
   *   The account code.
   *
   * @return $this
   */
  public function setCode(string $code);

  /**
   * Get the account department.
   *
   * @return string
   *   The account department.
   */
  public function getDepartment(): string;

  /**
   * Set the account department.
   *
   * @param string $department
   *   The account department.
   *
   * @return $this
   */
  public function setDepartment(string $department);

}
