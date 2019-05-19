<?php

namespace Drupal\uc_cart\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\uc_cart\CartInterface;
use Drupal\uc_cart\CartManagerInterface;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Drupal\uc_cart\Event\CheckoutReviewOrderEvent;
use Drupal\uc_cart\Event\CheckoutStartEvent;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Controller routines for the checkout.
 */
class CheckoutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime.time service.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager, CartManagerInterface $cart_manager, SessionInterface $session, TimeInterface $date_time) {
    $this->checkoutPaneManager = $checkout_pane_manager;
    $this->cartManager = $cart_manager;
    $this->session = $session;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane'),
      $container->get('uc_cart.manager'),
      $container->get('session'),
      $container->get('datetime.time')
    );
  }

  /**
   * Builds the cart checkout page from available checkout pane plugins.
   */
  public function checkout() {
    $cart_config = $this->config('uc_cart.settings');

    $items = $this->cartManager->get()->getContents();
    if (count($items) == 0 || !$cart_config->get('checkout_enabled')) {
      return $this->redirect('uc_cart.cart');
    }

    // Send anonymous users to login page when anonymous checkout is disabled.
    if ($this->currentUser()->isAnonymous() && !$cart_config->get('checkout_anonymous')) {
      $this->messenger()->addMessage($this->t('You must login before you can proceed to checkout.'));
      if ($this->config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
        $this->messenger()->addMessage($this->t('If you do not have an account yet, you should <a href=":url">register now</a>.', [':url' => Url::fromRoute('user.register', [], ['query' => $this->getDestinationArray()])->toString()]));
      }
      return $this->redirect('user.login', [], ['query' => $this->getDestinationArray()]);
    }

    // Load an order from the session, if available.
    if ($this->session->has('cart_order')) {
      $order = $this->loadOrder();
      if ($order) {
        // To prevent identity theft, don't use an existing order if it has
        // changed status or owner, or if there has been no activity for 10
        // minutes.
        $request_time = $this->dateTime->getRequestTime();
        if ($order->getStateId() != 'in_checkout' ||
            ($this->currentUser()->isAuthenticated() && $this->currentUser()->id() != $order->getOwnerId()) ||
            $order->getChangedTime() < $request_time - CartInterface::CHECKOUT_TIMEOUT) {
          if ($order->getStateId() == 'in_checkout' && $order->getChangedTime() < $request_time - CartInterface::CHECKOUT_TIMEOUT) {
            // Mark expired orders as abandoned.
            $order->setStatusId('abandoned')->save();
          }
          unset($order);
        }
      }
      else {
        // Ghost session.
        $this->session->remove('cart_order');
        $this->messenger()->addMessage($this->t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }

    // Determine if the form is being submitted or built for the first time.
    if (isset($_POST['form_id']) && $_POST['form_id'] == 'uc_cart_checkout_form') {
      // If this is a form submission, make sure the cart order is still valid.
      if (!isset($order)) {
        $this->messenger()->addMessage($this->t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
      elseif ($this->session->has('uc_cart_order_rebuild')) {
        $this->messenger()->addMessage($this->t('Your shopping cart contents have changed. Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }
    else {
      // Prepare the cart order.
      $rebuild = FALSE;
      if (!isset($order)) {
        // Create a new order if necessary.
        $order = Order::create([
          'uid' => $this->currentUser()->id(),
        ]);
        $order->save();
        $this->session->set('cart_order', $order->id());
        $rebuild = TRUE;
      }
      elseif ($this->session->has('uc_cart_order_rebuild')) {
        // Or, if the cart has changed, then remove old products and line items.
        $result = \Drupal::entityQuery('uc_order_product')
          ->condition('order_id', $order->id())
          ->execute();
        if (!empty($result)) {
          $storage = $this->entityTypeManager()->getStorage('uc_order_product');
          $entities = $storage->loadMultiple(array_keys($result));
          $storage->delete($entities);
        }
        uc_order_delete_line_item($order->id(), TRUE);
        $rebuild = TRUE;
      }

      if ($rebuild) {
        // Copy the cart contents to the cart order.
        $order->products = [];
        foreach ($items as $item) {
          $order->products[] = $item->toOrderProduct();
        }
        $this->session->remove('uc_cart_order_rebuild');
      }
      elseif (!uc_order_product_revive($order->products)) {
        $this->messenger()->addError($this->t('Some of the products in this order are no longer available.'));
        return $this->redirect('uc_cart.cart');
      }
    }

    $min = $cart_config->get('minimum_subtotal');
    if ($min > 0 && $order->getSubtotal() < $min) {
      $this->messenger()->addError($this->t('The minimum order subtotal for checkout is @min.', ['@min' => uc_currency_format($min)]));
      return $this->redirect('uc_cart.cart');
    }

    // Invoke the customer starts checkout hook.
    $this->moduleHandler()->invokeAll('uc_cart_checkout_start', [$order]);

    // Trigger the checkout start event.
    /* rules_invoke_event('uc_cart_checkout_start', $order); */
    $event = new CheckoutStartEvent($order);
    \Drupal::service('event_dispatcher')->dispatch($event::EVENT_NAME, $event);

    return $this->formBuilder()->getForm('Drupal\uc_cart\Form\CheckoutForm', $order);
  }

  /**
   * Allows a customer to review their order before finally submitting it.
   */
  public function review() {
    if (!$this->session->has('cart_order') || !$this->session->has('uc_checkout_review_' . $this->session->get('cart_order'))) {
      return $this->redirect('uc_cart.checkout');
    }

    $order = $this->loadOrder();

    if (!$order || $order->getStateId() != 'in_checkout') {
      $this->session->remove('uc_checkout_complete_' . $this->session->get('cart_order'));
      return $this->redirect('uc_cart.checkout');
    }
    elseif (!uc_order_product_revive($order->products)) {
      $this->messenger()->addError($this->t('Some of the products in this order are no longer available.'));
      return $this->redirect('uc_cart.cart');
    }

    $filter = ['enabled' => FALSE];

    // If the cart isn't shippable, bypass panes with shippable == TRUE.
    if (!$order->isShippable() && $this->config('uc_cart.settings')->get('panes.delivery.settings.delivery_not_shippable')) {
      $filter['shippable'] = TRUE;
    }

    $panes = $this->checkoutPaneManager->getPanes($filter);
    foreach ($panes as $pane) {
      $return = $pane->review($order);
      if (!is_null($return)) {
        $data[$pane->getTitle()] = $return;
      }
    }

    $build = [
      '#theme' => 'uc_cart_checkout_review',
      '#panes' => $data,
      '#form' => $this->formBuilder()->getForm('Drupal\uc_cart\Form\CheckoutReviewForm', $order),
    ];

    $build['#attached']['library'][] = 'uc_cart/uc_cart.styles';
    $build['#attached']['library'][] = 'uc_cart/uc_cart.review.scripts';

    // Invoke the customer reviews order checkout hook.
    $this->moduleHandler()->invokeAll('uc_cart_checkout_review_order', [$order]);

    // Trigger the checkout review order event.
    /* rules_invoke_event('uc_cart_checkout_review_order', $order); */
    $event = new CheckoutReviewOrderEvent($order);
    \Drupal::service('event_dispatcher')->dispatch($event::EVENT_NAME, $event);

    return $build;
  }

  /**
   * Completes the sale and finishes checkout.
   */
  public function complete() {
    if (!$this->session->has('cart_order') || !$this->session->has('uc_checkout_complete_' . $this->session->get('cart_order'))) {
      return $this->redirect('uc_cart.cart');
    }

    $order = $this->loadOrder();

    if (empty($order)) {
      // If order was lost, display customer message and log the occurrence.
      $this->messenger()->addError($this->t("We're sorry.  An error occurred while processing your order that prevents us from completing it at this time. Please contact us and we will resolve the issue as soon as possible."));
      $this->getLogger('uc_cart')->error('An empty order made it to checkout! Cart order ID: @cart_order', ['@cart_order' => $this->session->get('cart_order')]);
      return $this->redirect('uc_cart.cart');
    }

    $this->session->remove('uc_checkout_complete_' . $this->session->get('cart_order'));
    $this->session->remove('cart_order');

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, $this->t('Order created through website.'), 'admin');

    return $this->cartManager->completeSale($order);
  }

  /**
   * Loads the order that is being processed for checkout from the session.
   *
   * @return \Drupal\uc_order\OrderInterface
   *   The order object.
   */
  protected function loadOrder() {
    $id = $this->session->get('cart_order');
    // Reset uc_order entity cache then load order.
    $storage = $this->entityTypeManager()->getStorage('uc_order');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

}
