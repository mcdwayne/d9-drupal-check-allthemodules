<?php

namespace Drupal\pubkey_encrypt\EventSubscriber;

use Drupal\Core\Url;
use Drupal\pubkey_encrypt\PubkeyEncryptManager;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pubkey Encrypt event subscriber.
 */
class PubkeyEncryptSubscriber implements EventSubscriberInterface {

  /**
   * Pubkey Encrypt manager service.
   *
   * @var \Drupal\pubkey_encrypt\PubkeyEncryptManager
   */
  protected $pubkeyEncryptManager;

  /**
   * Constructor for PubkeyEncryptSubscriber.
   *
   * @param \Drupal\pubkey_encrypt\PubkeyEncryptManager $pubkey_encrypt_manager
   *   Pubkey Encrypt Manager service.
   */
  public function __construct(PubkeyEncryptManager $pubkey_encrypt_manager) {
    $this->pubkeyEncryptManager = $pubkey_encrypt_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('storeKeyInCookie');
    return $events;
  }

  /**
   * Temporarily store the Private key for a logged-in user, in a cookie.
   *
   * This cookie will be later used by PubkeyEncryptKeyProvider during key
   * retrievals.
   */
  public function storeKeyInCookie(FilterResponseEvent $event) {
    // Fetch the status of module.
    $module_initialized = $this->pubkeyEncryptManager->isModuleInitialized();

    // Proceed to set a cookie only if a user is logged-in and the module has
    // been initialized.
    if (\Drupal::currentUser()->isAuthenticated() && $module_initialized) {
      $cookies = $event->getRequest()->cookies;
      $cookie_name = \Drupal::currentUser()->id() . '_private_key';
      // Do nothing if the cookie already exists.
      if (!$cookies->get($cookie_name)) {
        // Otherwise, set the cookie. But it can only be set if a user JUST
        // logged in with his credentials. Because in that case, we can grab his
        // Private key from the user.shared_tempstore. See
        // PubkeyEncryptManager::userLoggedIn() for more details.
        $temp_store = \Drupal::service('user.shared_tempstore')
          ->get('pubkey_encrypt');
        $private_key = $temp_store->get($cookie_name);
        if ($private_key) {
          $cookie = new Cookie($cookie_name, $private_key);
          $event->getResponse()->headers->setCookie($cookie);
          // Since the cookie has been set, clear the Private Key from
          // tempstore.
          $temp_store->delete($cookie_name);
        }
        // If not possible to set the cookie, log-out the user so he could
        // log-in again.
        else {
          user_logout();
          $event->setResponse(new RedirectResponse(Url::fromRoute('<front>')->toString()));
        }
      }
    }
    // Otherwise if no user is logged-in, clear the relevant cookies if any.
    else {
      foreach ($event->getRequest()->cookies->keys() as $cookie) {
        if (preg_match('/private_key/', $cookie)) {
          $event->getResponse()->headers->clearCookie($cookie);
        }
      }
    }
  }

}
