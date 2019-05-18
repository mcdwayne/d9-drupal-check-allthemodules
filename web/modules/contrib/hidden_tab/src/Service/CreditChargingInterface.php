<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 2/11/19
 * Time: 4:57 PM
 */

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabCreditInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;

interface CreditChargingInterface {

  public const CHECK_ORDER_REPLACE = [
    ' ',
    '-',
    '/',
    '\\',
    '-',
    '+',
    '=',
    '|',
    '"',
    "'",
    ".",
    ";",
    "&",
    "*",
  ];

  /**
   * Kept between min valid credit and INFINITE credit value as invalid values.
   *
   * As a safety net so no one accidentally gives infinite credit with buffer
   * overflows, off by one and stuff.
   */
  public const INVALID_SAFETY_NET = [-1, -2];

  /**
   * User with this permission won't be charged `any` credit ever.
   */
  public const BYPASS_CHARGING_PERMISSION = 'bypass credit charging';

  /**
   * Amount of credit that denotes infinite credit.
   *
   * @see \Drupal\hidden_tab\Service\CreditCharging::isInfinite()
   */
  public const INFINITE = -3;

  /**
   * Find credit entity by params.
   *
   * TODO do not take full entity, get ID.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null $page
   *   The hidden tab page in question.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function he(?HiddenTabPageInterface $page,
                     ?EntityInterface $entity,
                     ?AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The account in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function peu(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function pex(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      bool $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param bool $entity
   *   Dummy.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function pxu(HiddenTabPageInterface $page,
                      bool $entity,
                      AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param bool $entity
   *   Dummy.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function pxx(?HiddenTabPageInterface $page,
                      bool $entity,
                      bool $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function xeu(bool $page,
                      EntityInterface $entity,
                      AccountInterface $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function xex(bool $page,
                      EntityInterface $entity,
                      bool $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param bool $entity
   *   Dummy.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[]
   *   Loaded entities
   */
  public function xxu(bool $page,
                      bool $entity,
                      AccountInterface $account): array;

  // ==========================================================================

  /**
   * Check credit, charge credit and return TRUE meaning account had credit.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $credit
   *   The credit to check credit of.
   * @param \Drupal\Core\Session\AccountInterface $from_user
   *   The user who is going to be charged credit.
   *
   * @return bool
   *   True if account has credit.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function charge(HiddenTabCreditInterface $credit,
                         AccountInterface $from_user): bool;

  /**
   * If credit can be charged from credit, that is it's enabled and has credit.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $credit
   *   Credit entity to check.
   *
   * @return bool
   *   True if it can.
   */
  public function canBeCharged(HiddenTabCreditInterface $credit): bool;

  /**
   * Check to see if credit value is in valid range.
   *
   * @param int|string $credit
   *   Amount of credit.
   *
   * @return bool
   *   True if credit is valid.
   */
  public function isValid($credit): bool;

  /**
   * Check to see if credit span value is in valid range.
   *
   * @param int|string $credit_span
   *   Amount of credit_span.
   *
   * @return bool
   *   True if credit span is valid.
   */
  public function isSpanValid($credit_span): bool;

  /**
   * Check to see if credit value is denoting infinite credit.
   *
   * @param int $credit
   *   Amount of credit.
   *
   * @throws \Exception
   *   In case credit is not in valid range.
   *
   * @return bool
   */
  public function isInfinite(int $credit): bool;

  /**
   * Set invalid values which credit can not (MUST not) be.
   *
   * @return array
   *   Set of invalid values.
   */
  public static function invalidValues(): array;

  /**
   * Minimum valid value credit can get.
   *
   * @return int
   *   Minimum valid value credit can get.
   */
  public function minValid(): int;

  /**
   * @param string $to_fix
   *
   * @return string[]
   */
  public function fixCreditCheckOrder(string $to_fix): array;

}