<?php

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabCreditInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\HttpFoundation\RequestStack;

class CreditCharging implements CreditChargingInterface {

  /**
   * Used by findCreditEntityById().
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $creditStorage;

  /**
   * To get current IP, for per ip accounting.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              RequestStack $request_stack) {
    $this->creditStorage = $entity_type_manager->getStorage('hidden_tab_credit');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Find entity by id.
   *
   * @param $id
   *   Id of entity
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCreditInterface
   *   Loaded entity if any.
   */
  private function findCreditEntityById($id): HiddenTabCreditInterface {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $credit */
    $credit = $this->creditStorage->load($id);
    return $credit;
  }

  // -------------------------------------------------------------- FIND CREDIT

  /**
   * {@inheritdoc}
   */
  public function he(?HiddenTabPageInterface $page,
                     ?EntityInterface $entity,
                     ?AccountInterface $account): array {
    if ($page === NULL && $entity === NULL && $account === NULL) {
      throw new \LogicException('illegal state');
    }
    $q = $this->creditStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE, '=');

    if (!$page) {
      $q->condition('target_hidden_tab_page', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_hidden_tab_page', $page->id(), '=');
    }

    if (!$entity) {
      $q->condition('target_entity', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_entity', $entity->id(), '=');
    }

    if (!$account) {
      $q->condition('target_user', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_user', $account->id(), '=');
    }

    $ret = [];
    foreach ($q->execute() as $id) {
      $ret[] = $this::findCreditEntityById($id);
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function peu(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      AccountInterface $account): array {
    return $this::he($page, $entity, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function pex(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      bool $account): array {
    if ($account) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, $entity, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function pxu(HiddenTabPageInterface $page,
                      bool $entity,
                      AccountInterface $account): array {
    if ($entity) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, NULL, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function pxx(?HiddenTabPageInterface $page,
                      bool $entity,
                      bool $account): array {
    if ($entity || $account) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, NULL, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function xeu(bool $page,
                      EntityInterface $entity,
                      AccountInterface $account): array {
    if ($page) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, $entity, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function xex(bool $page,
                      EntityInterface $entity,
                      bool $account): array {
    if ($page || $account) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, $entity, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function xxu(bool $page,
                      bool $entity,
                      AccountInterface $account): array {
    if ($page || $entity) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, NULL, $account);
  }

  // ----------------------------------------------------------------- CHARGING

  /**
   * {@inheritdoc}
   */
  public function charge(HiddenTabCreditInterface $credit,
                         AccountInterface $from_user): bool {
    // TODO may also reset span?
    if ($from_user->hasPermission(CreditChargingInterface::BYPASS_CHARGING_PERMISSION)) {
      return TRUE;
    }

    if ($credit->credit() < -2) {
      return TRUE;
    }

    $key = $credit->isPerIp() ? $this->request->getClientIp() : 'everyone';
    $cfg = NULL;

    if ($credit->creditSpan() > 0) {
      $cfg = $credit->ipAccounting(CreditCharging::class) ?: [];
      $last = isset($cfg[$key]) ? $cfg[$key] : PHP_INT_MAX - 99999999999;
      if (\time() < ($last + $credit->creditSpan())) {
        return TRUE;
      }
    }

    if ($credit->credit() === 0) {
      if ($from_user->hasPermission(Utility::ADMIN_PERMISSION)) {
        \Drupal::messenger()->addWarning('administrative view');
        return TRUE;
      }
      return FALSE;
    }

    if ($from_user->hasPermission(Utility::ADMIN_PERMISSION)) {
      \Drupal::messenger()->addWarning('administrative view');
      return TRUE;
    }

    $credit->set('credit', $credit->credit() - 1);
    if ($credit->creditSpan() > 0) {
      $cfg[$key] = \time();
      $credit->setIpAccounting(CreditCharging::class, $cfg);
    }
    $credit->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($credit): bool {
    if ((((int) $credit) . '') !== ($credit . '')) {
      return FALSE;
    }
    $credit = (int) $credit;
    return !in_array($credit, static::invalidValues())
      && $credit >= static::minValid();
  }

  /**
   * {@inheritdoc}
   */
  public function isSpanValid($credit_span): bool {
    if ((((int) $credit_span) . '') !== ($credit_span . '')) {
      return FALSE;
    }
    return ((int) $credit_span) >= 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isInfinite(int $credit): bool {
    if (!$this->isValid($credit)) {
      throw new \LogicException('illegal state');
    }
    return $credit === CreditChargingInterface::INFINITE;
  }

  /**
   * {@inheritdoc}
   */
  public static function invalidValues(): array {
    return CreditChargingInterface::INVALID_SAFETY_NET;
  }

  /**
   * {@inheritdoc}
   */
  public function minValid(): int {
    return CreditChargingInterface::INFINITE;
  }

  /**
   * {@inheritdoc}
   */
  public function fixCreditCheckOrder(string $to_fix): array {
    $with = ',';
    $double_with = ',,';

    foreach (CreditChargingInterface::CHECK_ORDER_REPLACE as $r) {
      $to_fix = str_replace($r, $with, $to_fix);
    }
    while (strpos($to_fix, $double_with) !== FALSE) {
      $to_fix = str_replace($double_with, $with, $to_fix);
    }
    $fix = explode($with, $to_fix);
    $ok = [
      'peu',
      'pex',
      'pxu',
      'pxx',
      'xeu',
      'xex',
      'xxu',
    ];
    foreach ($fix as $order) {
      if (!in_array($order, $ok, TRUE)) {
        \Drupal::logger('hidden_tab')
          ->error('bad access order value order={order}', [
            'order' => $to_fix,
          ]);
        return [];
      }
    }
    return $fix;
  }

  /**
   * {@inheritdoc}
   */
  public function canBeCharged(HiddenTabCreditInterface $credit): bool {
    return $credit->isEnabled() &&
      ($this->isInfinite($credit->credit()) || $credit->credit() > 0);
  }

  /**
   * Factory method, create an instance of the service.
   *
   * @return \Drupal\hidden_tab\Service\CreditChargingInterface
   */
  public static function instance(): CreditChargingInterface {
    return \Drupal::service('hidden_tab.credit_service');
  }

}
