<?php

namespace Drupal\redirect_node\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event handler to process redirection of a content type.
 *
 * @see
 *   https://www.thirdandgrove.com/redirecting-node-pages-drupal-8
 */
class RedirectRedirectNodes implements EventSubscriberInterface {

  /**
   * Currently signed in user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManger;

  /**
   * Constructs a RedirectRedirectNodes object.
   *
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManger
   *   The Entity type manager.
   */
  public function __construct(AccountProxy $currentUser, EntityTypeManagerInterface $entityTypeManger) {
    $this->currentUser = $currentUser;
    $this->entityTypeManger = $entityTypeManger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This announces which events you want to subscribe to.
    // We only need the request event for this example.  Pass
    // this an array of method names.
    return([
      KernelEvents::REQUEST => [
        ['redirect'],
      ],
    ]);
  }

  /**
   * Redirect requests for `redirect` nodes to their destination url.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event object of current event.
   */
  public function redirect(GetResponseEvent $event) {
    $request = $event->getRequest();

    // This is necessary because this also gets called on
    // node sub-tabs such as "edit", "revisions", etc.  This
    // prevents those pages from redirected.
    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }

    // Only redirect a certain content type.
    $node = $request->attributes->get('node');
    if (empty($node) || $node->getType() !== 'redirect') {
      return;
    }

    $roles = $this->entityTypeManger->getStorage('user_role')->loadMultiple($this->currentUser->getRoles());

    $isAdmin = array_reduce($roles, function ($carry, $item) {
      return $carry || $item->isAdmin();
    });

    // If user can edit the page, or is an admin, they should not be redirected.
    // If a user can't view the node, they should not be redirect and get a 403.
    if ($isAdmin || !$node->access() || $node->access('edit')) {
      return;
    }

    // Get the destination url.
    $destination = $this->getDestination($node);

    if (!empty($destination)) {

      // Convert e.g. internal:/ scheme paths.
      $destination = Url::fromUri($destination)->toString();

      // Create Redirect to the external URL.
      $response = new TrustedRedirectResponse($destination);

      // Don't Cache the redirect.
      $build = [
        '#cache' => [
          'contexts' => 'user',
        ],
      ];
      $response->addCacheableDependency($build, $this->currentUser);

      // Execute the redic.
      $event->setResponse($response);

    }
  }

  /**
   * Calculate the redirection url from the node.
   *
   * @param Drupal\node\Entity\NodeInterface
   *   The currently loaded redirect node.
   * @return string
   *   Destination url to send the user to.
   */
  protected function getDestination(NodeInterface $node) {
    $uris = array_column($node->redirect_destination->getValue(), 'uri');
    $destination = array_shift($uris);
    return $destination;
  }

}
