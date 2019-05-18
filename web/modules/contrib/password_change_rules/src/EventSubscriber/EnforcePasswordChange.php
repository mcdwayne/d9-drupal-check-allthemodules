<?php

namespace Drupal\password_change_rules\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enforce the user changes their password.
 */
class EnforcePasswordChange implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $storage;

  /**
   * EnforceBasicInfoSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $account;
    $this->storage = $entityTypeManager->getStorage('user');
  }

  /**
   * Force the user to change their password.
   */
  public function enforcePasswordChange(FilterResponseEvent $event) {
    $request = $event->getRequest();

    // If they're not logged in or already or the correct page do nothing.
    if ($this->currentUser->isAnonymous() || in_array($request->get('_route'), $this->getPasswordChangeRoutes())) {
      return;
    }

    $account = $this->storage->load($this->currentUser->id());
    if (!$account->get('password_change_rules')->value) {
      return;
    }

    // If the response is a local redirect already then we hijack it and set our
    // own URI. This happens right after a user logs in.
    $response = $event->getResponse();
    if ($response instanceof LocalRedirectResponse) {
      $event->getResponse()->setTargetUrl($this->getUrl());
      return;
    }

    // If it isn't a HTML response then don't do any redirecting.
    if (!$response instanceof HtmlResponse) {
      return;
    }

    // Set a redirect response.
    $event->setResponse(new RedirectResponse($this->getUrl()));
  }

  /**
   * Gets the redirect URL for users who are required to change their password.
   *
   * @return string
   *   The url we'll redirect to if they must change their password.
   */
  protected function getUrl() {
    // Always use the first route.
    return $url = Url::fromRoute($this->getPasswordChangeRoutes()[0], ['user' => $this->currentUser->id()])
      ->setAbsolute()
      ->toString();
  }

  /**
   * Gets the password change form route.
   *
   * @return string
   *   The route name for the password change form.
   */
  protected function getPasswordChangeRoutes() {
    // @TODO, allow this to be altered in case other modules provide other valid
    // password change URLs.
    return [
      // The user edit form is the correct page to change your password.
      'entity.user.edit_form',
      // One-time login should not redirect either.
      'user.reset.login',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'enforcePasswordChange',
    ];
  }

}
