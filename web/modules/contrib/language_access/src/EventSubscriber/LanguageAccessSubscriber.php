<?php

namespace Drupal\language_access\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirect .html pages to corresponding Node page.
 */
class LanguageAccessSubscriber implements EventSubscriberInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs LanguageAccess Subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    AccountInterface $current_user,
    LanguageManagerInterface $language_manager,
    RequestStack $request_stack
  ) {
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Redirect pattern based url.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   A GetResponseEvent instance.
   */
  public function customLanguageAccess(GetResponseEvent $event) {

    $request = $this->requestStack->getCurrentRequest();
    $requestUrl = $request->server->get('REQUEST_URI', NULL);
    $language = $this->languageManager->getCurrentLanguage();

    // Allow user path.
    if (strpos($requestUrl, '/user/') === FALSE) {
      // Check access to language.
      if (!$this->currentUser->hasPermission('access language ' . $language->getId())) {
        $default_language = $this->languageManager->getDefaultLanguage();
        // We still want to allow access to default language.
        if ($language->getId() !== $default_language->getId()) {
          // Do not execute on drush.
          if (PHP_SAPI != 'cli') {
            // Display the default access denied page.
            if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
              throw new AccessDeniedHttpException();
            }
          }
        }
      }
    }
  }

  /**
   * Listen to kernel.request events and call customRedirection.
   *
   * @return array
   *   Event names to listen to (key) and methods to call (value).
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['customLanguageAccess'];
    return $events;
  }

}
