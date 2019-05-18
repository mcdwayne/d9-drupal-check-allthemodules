<?php

namespace Drupal\new_relic_rpm\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Request event listener to set transaction name and flag ignore/background.
 */
class NewRelicRequestSubscriber implements EventSubscriberInterface {

  /**
   * New Relic adapter.
   *
   * @var \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface
   */
  protected $adapter;

  /**
   * The object we use for matching paths.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The configuration for the New Relic RPM module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * An object representing the current URL path of the request.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * A flag whether the master request was processed.
   *
   * @var bool
   */
  protected $processedMasterRequest = FALSE;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a subscriber.
   *
   * @param \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface $adapter
   *   The Adapter that we use to talk to the New Relic extension.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The object we use for matching paths.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The object we use to get our settings.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   An object representing the current URL path of the request.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   An object representing the current user.
   */
  public function __construct(NewRelicAdapterInterface $adapter, PathMatcherInterface $path_matcher, ConfigFactoryInterface $config_factory, CurrentPathStack $current_path_stack, AccountInterface $current_user) {
    $this->adapter = $adapter;
    $this->pathMatcher = $path_matcher;
    $this->config = $config_factory->get('new_relic_rpm.settings');
    $this->currentPathStack = $current_path_stack;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // Run after RouterListener, which has priority 32.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

  /**
   * Set the desired transaction state and name.
   *
   * Naming is based on the current path and route.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The current response event for the page.
   */
  public function onRequest(GetResponseEvent $event) {

    // If this is a sub request, only process it if there was no master
    // request yet. In that case, it is probably a page not found or access
    // denied page.
    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST && $this->processedMasterRequest) {
      return;
    }

    $ignore_roles = $this->config->get('ignore_roles');
    $ignore_urls = $this->config->get('ignore_urls');
    $bg_urls = $this->config->get('bg_urls');
    $exclude_urls = $this->config->get('exclusive_urls');

    if (!empty($ignore_roles)) {
      $user_roles = $this->currentUser->getRoles();
      foreach ($ignore_roles as $ignored_role) {
        if (in_array($ignored_role, $user_roles)) {
          return $this->adapter->setTransactionState(NewRelicAdapterInterface::STATE_IGNORE);
        }
      }
    }

    $path = ltrim($this->currentPathStack->getPath(), '/');
    if (!empty($exclude_urls)) {
      if (!$this->pathMatcher->matchPath($path, $exclude_urls)) {
        return $this->adapter->setTransactionState(NewRelicAdapterInterface::STATE_IGNORE);
      }
    }

    if (!empty($ignore_urls)) {
      if ($this->pathMatcher->matchPath($path, $ignore_urls)) {
        return $this->adapter->setTransactionState(NewRelicAdapterInterface::STATE_IGNORE);
      }
    }

    if (!empty($bg_urls)) {
      if ($this->pathMatcher->matchPath($path, $bg_urls)) {
        $this->adapter->setTransactionState(NewRelicAdapterInterface::STATE_BACKGROUND);
      }
    }

    // If the path was not ignored, set the transaction mame.
    if ($name = $event->getRequest()->attributes->get('_transaction_name')) {
      $this->adapter->setTransactionName($name);
    }

    $this->processedMasterRequest = TRUE;
  }

}
