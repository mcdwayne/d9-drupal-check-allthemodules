<?php

namespace Drupal\commerce_quickpay_gateway\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Endpoints for the routes defined.
 */
class CallbackController extends ControllerBase {
  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Callback action.
   *
   * Listen for callbacks from QuickPay and creates any payment specified.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function callback(Request $request) {
    $content = json_decode($request->getContent());

    $order = Order::load($content->variables->order);

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => $content->accepted ? 'Accepted' : 'Failed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $content->variables->payment_gateway,
      'order_id' => $order->id(),
      'remote_id' => $content->id,
      'remote_state' =>  $this->getRemoteState($content),
    ]);

    $payment->save();

    return new Response();
  }

  /**
   * Get the state from the transaction.
   *
   * @param object $content
   *   The request data from QuickPay.
   *
   * @return string
   */
  private function getRemoteState($content) {
    $latest_operation = end($content->operations);
    return $latest_operation->qp_status_msg;
  }
}
