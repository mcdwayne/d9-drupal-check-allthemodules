<?php

namespace Drupal\domain_301_redirect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DomainRedirectEventSubscriber.
 *
 * @package Drupal\domain_301_redirect
 */
class DomainRedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * A config object for the Domain 301 Redirect configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Current user acocunt.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $userAccount;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a DomainRedirectEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $user_account
   *   The current user object.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Path Matcher interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, AccountProxyInterface $user_account, PathMatcherInterface $path_matcher) {
    $this->config = $config_factory->get('domain_301_redirect.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->userAccount = $user_account;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events['kernel.request'] = ['requestHandler'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @todo Needs a service which will handle the exclusion/inclusion of
   * the mentioned path/page.
   *
   * @param GetResponseEvent $event
   *   The response event.
   */
  public function requestHandler(GetResponseEvent $event) {
    // If domain redirection is not enabled, then no need to process further.
    if (!$this->config->get('enabled')) {
      return;
    }

    // If user has 'bypass' permission, then no need to process further.
    if ($this->userAccount->hasPermission('bypass domain 301 redirect')) {
      return;
    }

    // Check the path configuration to see if we should bypass redirection.
    if ($this->checkPath()) {
      return;
    }

    // If domain doesn't contain http/https, then add those to domain.
    $domain = $this->config->get('domain');
    if (!preg_match('|^https?://|', $domain)) {
      $domain = 'http://' . $domain;
    }

    // Parse the domain to get various settings like port.
    $domain_parts = parse_url($domain);
    $parsed_domain = $domain_parts['host'];
    $parsed_domain .= !empty($domain_parts['port']) ? ':' . $domain_parts['port'] : '';

    // If we're not on the same host, the user has access and this page isn't
    // an exception, redirect.
    if (($parsed_domain != $this->request->server->get('HTTP_HOST'))) {
      $uri = $this->request->getRequestUri();
      $response = new RedirectResponse($domain . $uri, 301);
      $response->send();
    }
  }

  /**
   * Checks if the current path should be protected or not.
   *
   * @return bool
   *   Whether to bypass the redirection or not.
   */
  private function checkPath() {
    // Get current path but always get the alias.
    $current_path = $this->request->getRequestUri();
    $path = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);

    $path_match = FALSE;
    $bypass = FALSE;

    if ($this->pathMatcher->matchPath($path, $this->config->get('pages'))) {
      $path_match = TRUE;
    }

    switch ($this->config->get('applicability')) {
      case DOMAIN_301_REDIRECT_EXCLUDE_METHOD:
        if ($path_match) {
          $bypass = TRUE;
        }
        break;

      case DOMAIN_301_REDIRECT_INCLUDE_METHOD:
        if (!$path_match) {
          $bypass = TRUE;
        }
        break;
    }

    return $bypass;
  }

}
