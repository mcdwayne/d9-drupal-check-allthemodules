<?php

namespace Drupal\commerce_currency_switcher\Cache\Context;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the CurrencyCacheContext service, for "per currency" caching.
 *
 * Cache context ID: 'multicurrency'.
 */
class CurrencyCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;


  /**
   * Constructs a new CurrencyCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AccountInterface $account, RequestStack $request_stack) {
    $this->account = $account;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Current currency');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->request->getSession()->get('selected_currency');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    $metadata->setCacheMaxAge(Cache::PERMANENT);
    return $metadata;
  }
}
