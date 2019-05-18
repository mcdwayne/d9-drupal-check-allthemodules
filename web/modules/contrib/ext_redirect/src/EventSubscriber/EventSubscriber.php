<?php

namespace Drupal\ext_redirect\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ext_redirect\Service\CurrentUrlInterface;
use Drupal\ext_redirect\Service\ExtRedirectConfig;
use Drupal\ext_redirect\Service\RedirectRuleMatcher;
use Drupal\ext_redirect\Service\RedirectRuleMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestSubscriber.
 *
 * @package Drupal\ext_redirect
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Current request host
   *
   * @var string
   */
  protected $host;

  protected $destination;

  protected $is404 = FALSE;

  /**
   * @var \Drupal\ext_redirect\Service\CurrentUrlInterface
   */
  protected $currentUrl;

  /**
   * @var \Drupal\ext_redirect\Entity\RedirectRule|null
   */
  protected $match;

  /**
   * @var \Drupal\ext_redirect\Service\ExtRedirectConfig
   */
  protected $extRedirectConfig;

  /**
   * @var RedirectRuleMatcherInterface
   */
  protected $matcher;

  /**
   * Constructor.
   */
  public function __construct(CurrentUrlInterface $current_url, ExtRedirectConfig $config, RedirectRuleMatcherInterface $matcher) {
    $this->currentUrl = $current_url;
    // We need to set host here, because can be altered later upon request processing.
    $this->host = $this->currentUrl->getHost();
    $this->extRedirectConfig = $config;
    $this->matcher = $matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkRequestAndRedirectIfExists', 31];
    $events[KernelEvents::EXCEPTION][] = ['setHttpException', 50];
    $events[KernelEvents::RESPONSE][] = [
      'overrideResponseObjectIfHttpException',
      28,
    ];
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @param Event $event
   *   The repsonse event.
   */
  public function checkRequestAndRedirectIfExists(Event $event) {

    if ($this->shouldSkip()) {
      return FALSE;
    }

    $host = $this->host;
    $primary_host = $this->extRedirectConfig->getPrimaryHost();
    $path = $this->currentUrl->getPath();
    $match = $this->matcher->lookup($host, $path);

    if (!$match) {
      if ($host != $primary_host) {
        $message = 'Missing matching rule for: ' . $host . $path;
        \Drupal::logger('ext_redirect')->warning($message);

        // This is the fallback if no rule has been found but the request host
        // does not match the primary host.
        // Would be better to have rule for this case.
        // Example from redirects conf. redirect to primary host and keep
        //
        //
        // RewriteCond %{HTTP_HOST}:%{ENV:base_host} !^(.+):\1$
        // RewriteCond %{HTTP_HOST} !^sample.dev:8080$
        // RewriteRule ^/?(.*)$ %{ENV:base_url}/$1 [R=301,L,QSD]
        //
        // TODO: Implement rule that redirects to primary host keeping request_uri.
        $scheme = $this->currentUrl->getScheme();
        $destination = $scheme . '://' . $primary_host . '' . $path;
        $status_code = 301;

        $response = new TrustedRedirectResponse($destination, $status_code);
        $this->destination = $destination;
        return $event->setResponse($response);

      }
      return FALSE;
    }

    $this->match = $match;
    $destination = $match->getDestinationUrl()->toString();
    $scheme = $this->currentUrl->getScheme();

    if (($options = $this->match->getDestinationUrlOptions())) {
      $options = unserialize($options);
    } else {
      $options = [];
    }

    // We need to handle internal and entity schemes.
    if (strpos($destination, 'internal:/') !== FALSE || strpos($destination, 'entity:') !== FALSE) {
      // We are not able to create an absolute URL here. Cause that way we
      // have the wrong host (the one the user requested). We need to "inject"
      // the correct host later.
      $url = Url::fromUri($destination, $options);
      $destination = $url->toString();
      $destination = $scheme . '://' . $primary_host . '' . $destination;
    }
    // Disable the caching of the redirect.
    \Drupal::service('page_cache_kill_switch')->trigger();
    // Create the response.
    $response = new TrustedRedirectResponse($destination, $match->getStatusCode());
    $this->destination = $destination;
    return $event->setResponse($response);
  }

  /**
   * This method is called whenever the kernel.exception event is dispatched.
   *
   * @param GetResponseForExceptionEvent $event
   *    An event object.
   */
  public function setHttpException(GetResponseForExceptionEvent $event) {
    if ($this->shouldSkip() || !$this->isRequestByAlias()) {
      return;
    }
    /*
     * @var $exception \Symfony\Component\HttpKernel\Exception\HttpException
     */
    $exception = $event->getException();
    if ($exception && $exception instanceof HttpException && $exception->getStatusCode() == 404) {
      $this->is404 = TRUE;
    }
  }

  /**
   * This method is called whenever the kernel.response event is dispatched.
   *
   * @param FilterResponseEvent $event
   *     An event object.
   */
  public function overrideResponseObjectIfHttpException(FilterResponseEvent $event) {

    if ($this->shouldSkip()) {
      return;
    }

    $response = $event->getResponse();

    if (
      ($this->is404 && isset($this->destination) && $this->isRequestByAlias())
      ||
      (isset($this->destination) && $this->destination <> $response->getTargetUrl())
    ) {
      /*
       * Most request from aliases leads to 404 by default
       * OR in case of redirect from not existing internal path
       * destination must be set again.
       */
      return $response->setTargetUrl($this->destination);
    }
  }

  /**
   * Indicates if current path is valid for rewrite checking.
   *
   * @TODO add ajax request detection.
   *
   * @return bool
   *    TRUE if current path is not supposed for rewrite checking.
   */
  private function shouldSkip() {
    return $this->currentUrl->isAdminPath() ||
      strpos($this->currentUrl->getPath(), 'sites/default/files') !== FALSE
      || !$this->extRedirectConfig->getPrimaryHost();
  }

  /**
   * Indicates if site is accessed by an alias.
   *
   * @return bool
   *    TRUE if request host is an alias.
   */
  private function isRequestByAlias() {
    return $this->host <> $this->extRedirectConfig->getPrimaryHost();
  }

}
