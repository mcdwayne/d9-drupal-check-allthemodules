<?php

namespace Drupal\agreement\EventSubscriber;

use Drupal\agreement\AgreementHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Checks if the current user is required to accept an agreement.
 */
class AgreementSubscriber implements EventSubscriberInterface {

  /**
   * Agreement handler.
   *
   * @var \Drupal\agreement\AgreementHandlerInterface
   */
  protected $handler;

  /**
   * Current path getter because paths > routes for users.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathStack;

  /**
   * Session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Initialize method.
   *
   * @param \Drupal\agreement\AgreementHandlerInterface $agreementHandler
   *   The agreement handler.
   * @param \Drupal\Core\Path\CurrentPathStack $pathStack
   *   The current path.
   * @param \Drupal\Core\Session\SessionManagerInterface $sessionManager
   *   The session manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(AgreementHandlerInterface $agreementHandler, CurrentPathStack $pathStack, SessionManagerInterface $sessionManager, AccountProxyInterface $account) {
    $this->handler = $agreementHandler;
    $this->pathStack = $pathStack;
    $this->sessionManager = $sessionManager;
    $this->account = $account;
  }

  /**
   * Check if the user needs to accept an agreement.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Users with the bypass agreement permission are always excluded from any
    // agreement.
    if (!$this->account->hasPermission('bypass agreement')) {
      $path = $this->pathStack->getPath($event->getRequest());
      $info = $this->handler->getAgreementByUserAndPath($this->account, $path);
      if ($info) {
        // Save intended destination.
        // @todo figure out which of this is still necessary.
        if (!isset($_SESSION['agreement_destination'])) {
          if (preg_match('/^user\/reset/i', $path)) {
            $_SESSION['agreement_destination'] = 'change password';
          }
          else {
            $_SESSION['agreement_destination'] = $path;
          }
        }

        // Redirect to the agreement page.
        $event->setResponse(new RedirectResponse($info->get('path')));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    // Dynamic page cache will redirect to a cached page at priority 27.
    $events[KernelEvents::REQUEST][] = ['checkForRedirection', 28];
    return $events;
  }

}
