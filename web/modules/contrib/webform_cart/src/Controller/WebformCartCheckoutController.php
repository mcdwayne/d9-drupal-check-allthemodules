<?php

namespace Drupal\webform_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform_cart\WebformCartInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebformCartCheckoutController.
 */
class WebformCartCheckoutController extends ControllerBase {

  protected $webformCart;

  /**
   * WebformCartCheckoutController constructor.
   *
   * @param \Drupal\webform_cart\WebformCartInterface $webform_cart
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\webform_cart\WebformCartSessionInterface $webform_cart_session
   */
  public function __construct(WebformCartInterface $webform_cart) {
    $this->webformCart = $webform_cart;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_cart.webformcart')
    );
  }

  /**
   * @return mixed
   */
  public function confirmOrder() {
    return $this->webformCart->getCheckout();
  }

  /**
   * @param $itemId
   *
   * @return mixed
   */
  public function deleteItem($itemId) {
    $this->webformCart->setDestination($_SERVER['HTTP_REFERER']);
    return $this->webformCart->removeItem($itemId);
  }

}
