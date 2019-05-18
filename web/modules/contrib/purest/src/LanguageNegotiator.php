<?php

namespace Drupal\purest;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class LanguageNegotiator.
 */
class LanguageNegotiator implements LanguageNegotiatorInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\language\ConfigurableLanguageManagerInterface definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Drupal\language\LanguageNegotiatorInterface definition.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new LanguageNegotiator object.
   */
  public function __construct(RequestStack $request_stack, ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $language_negotiator, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $request_stack;
    $this->languageManager = $language_manager;
    $this->languageNegotiator = $language_negotiator;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

}
