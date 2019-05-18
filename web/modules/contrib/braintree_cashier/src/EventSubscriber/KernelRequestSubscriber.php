<?php

namespace Drupal\braintree_cashier\EventSubscriber;

use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class KernelRequestSubscriber.
 */
class KernelRequestSubscriber implements EventSubscriberInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The temporary storage service.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs a new KernelRequestSubscriber object.
   */
  public function __construct(RouteMatchInterface $routeMatch, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, SubscriptionService $subscriptionService, BillableUser $billableUser, RequestStack $requestStack, KillSwitch $killSwitch) {

    $this->routeMatch = $routeMatch;
    $this->currentUser = $currentUser;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->subscriptionService = $subscriptionService;
    $this->billableUser = $billableUser;
    $this->requestStack = $requestStack;
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['kernelRequest'];

    return $events;
  }

  /**
   * The event handler which monitors the braintree_cashier.signup_form route.
   *
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The symfony event.
   */
  public function kernelRequest(GetResponseEvent $event) {

    if ($this->routeMatch->getRouteName() == 'braintree_cashier.signup_form') {
      // Don't cache this page even when anonymous page caching is enabled.
      $this->killSwitch->trigger();
      // Redirect anonymous users to registration.
      if ($this->currentUser->isAnonymous()) {
        $plan_id = $this->requestStack->getCurrentRequest()->query->get('plan_id');
        if (!empty($plan_id)) {
          $this->requestStack->getCurrentRequest()->getSession()->set('plan_id', $plan_id);
        }
        $url = Url::fromRoute('user.register');

        $response = new RedirectResponse($url->toString());
        $event->setResponse($response);
        return;
      }
      // Redirect existing customers to the My Subscription tab.
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->userStorage->load($this->currentUser->id());
      if (!empty($this->billableUser->getBraintreeCustomerId($user))) {
        // Redirect to My Subscription tab on user profile.
        $url = Url::fromRoute('braintree_cashier.my_subscription', [
          'user' => $this->currentUser->id(),
        ]);
        $plan_id = $this->requestStack->getCurrentRequest()->query->get('plan_id');
        if (!empty($plan_id)) {
          $url->setOption('query', [
            'plan_id' => $plan_id,
          ]);
        }
        $response = new RedirectResponse($url->toString());
        $event->setResponse($response);
        return;
      }
    }
  }

}
