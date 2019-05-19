<?php

namespace Drupal\trusted_redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Trusted redirect subscriber to redirect to trusted hosts.
 */
class TrustedRedirectSubscriber implements EventSubscriberInterface {

  /**
   * List of trusted hosts.
   *
   * @var array
   */
  protected $trustedHosts;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ImmutableConfig $config, ModuleHandlerInterface $module_handler) {
    $this->trustedHosts = $config->get('trusted_hosts');
    $this->moduleHandler = $module_handler;
  }

  /**
   * Obtain list of trusted hosts.
   *
   * @return array
   *   List of trusted hosts.
   */
  protected function getTrustedHosts() {
    $trusted_hosts = $this->trustedHosts;
    // Let other modules alter the list of trusted hosts.
    $this->moduleHandler->alter('trusted_redirect_hosts', $trusted_hosts);
    return $trusted_hosts;
  }

  /**
   * Evaluates whether destination url is trusted or not.
   *
   * @param string $destination
   *   Destination url.
   *
   * @return bool
   *   Whether destination url is trusted or not.
   */
  protected function isTrustedDestination($destination) {
    if (!$destination) {
      return FALSE;
    }
    $trusted_hosts = $this->getTrustedHosts();
    $url_info = parse_url($destination);
    if (!isset($url_info['host'])) {
      return FALSE;
    }
    return in_array($url_info['host'], $trusted_hosts);
  }

  /**
   * Redirect to trusted host.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondRedirectToTrustedHost(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $request = $event->getRequest();
      // Get trusted destination from request query parameter bag.
      $trusted_destination = $request->get('trusted_destination');
      if ($this->isTrustedDestination($trusted_destination)) {
        // Redirect to trusted destination.
        $response->setTargetUrl($trusted_destination);
        $event->stopPropagation();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority of 1 to run before RedirectResponseSubscriber to act before
    // destination parameter is processed and evaluated with exception.
    $events[KernelEvents::RESPONSE][] = ['onRespondRedirectToTrustedHost', 1];
    return $events;
  }

}
