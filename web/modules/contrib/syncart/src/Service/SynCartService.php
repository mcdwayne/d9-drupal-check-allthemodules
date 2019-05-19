<?php

namespace Drupal\syncart\Service;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_store\Entity\Store;
use Drupal\image\Entity\ImageStyle;

/**
 * Class SynCartService.
 */
class SynCartService implements SynCartServiceInterface {

  /**
   * Провайдер корзины.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Объект текущей корзины.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * Constructs a new SynCartService object.
   */
  public function __construct(CartProviderInterface $cart_provider) {
    $this->cartProvider = $cart_provider;
    // Получение текущей корзины.
    $this->cart = $this->cartProvider->getCart('default');
    // Проверка на наличие дублирующих корзин.
    $this->clearCarts();
    $this->vid = 0;
  }

  /**
   * Получить текущую корзину пользователя.
   */
  public function load() {
    return $this->cart;
  }

  /**
   * Добавить товар в корзину.
   */
  public function addItem($vid, $quantity = 1) {
    if (empty($this->cart)) {
      $this->createCart();
    }
    $this->vid = $vid;
    $variation = \Drupal::entityTypeManager()
      ->getStorage('commerce_product_variation')
      ->load($vid);
    if (is_object($variation)) {
      $this->setOrderItem($variation, $quantity);
      $this->cart->recalculateTotalPrice();
      $this->cart->save();
    }
    return $this->cart;
  }

  /**
   * Удалить товар из корзины.
   */
  public function removeOrderItem($orderItemId) {
    $orderItem = OrderItem::load($orderItemId);
    if (is_object($orderItem)) {
      $this->cart->removeItem($orderItem);
      $this->cart->recalculateTotalPrice();
      return $this->cart->save();
    }
    return FALSE;
  }

  /**
   * Обновить количество товара в корзине.
   */
  public function setOrderItemQuantity($orderItemId, $quantity = 1) {
    if (empty($orderItemId) || !is_numeric($orderItemId)) {
      return FALSE;
    }
    if (is_object($orderItem = OrderItem::load($orderItemId))) {
      $orderItem->setQuantity($quantity);
      $this->cart->save();
      $this->cart->recalculateTotalPrice();
      return $this->cart->save();
    }
    return FALSE;
  }

  /**
   * Если в заказе есть вариация, увеличить кол-во, иначе добавить новую.
   */
  private function setOrderItem($variation, $quantity) {
    $hasItem = FALSE;
    if (!is_object($this->cart)) {
      return;
    }
    foreach ($this->cart->getItems() as $cartItem) {
      if ($cartItem->getPurchasedEntityId() == $this->vid) {
        $newQuantity = (int) ($cartItem->getQuantity() + $quantity);
        $cartItem->setQuantity($newQuantity);
        $cartItem->save();
        $hasItem = TRUE;
        break;
      }
    }
    // Если вариация ранее не была добавлена, добавляем.
    if (!$hasItem) {
      $orderItem = \Drupal::entityTypeManager()
        ->getStorage('commerce_order_item')
        ->createFromPurchasableEntity($variation, ['quantity' => $quantity]);
      if (is_object($orderItem)) {
        $this->cart->addItem($orderItem);
      }
    }
    $this->cart->save();
  }

  /**
   * Объединение и очистка дублирующихся корзин.
   */
  private function clearCarts() {
    $carts = $this->cartProvider->getCarts();
    if (count($carts) > 1) {
      $goods = [];
      foreach ($carts as $key => $otherCart) {
        if ($otherCart->id() !== $this->cart->id()) {
          foreach ($otherCart->getItems() as $cartItem) {
            $otherCart->removeItem($cartItem);
            $pid = $cartItem->purchased_entity->target_id;
            $quantity = $cartItem->quantity->value;
            if (isset($goods[$pid])) {
              $goods[$pid] += $quantity;
            }
            else {
              $goods[$pid] = $quantity;
            }
          }
          $otherCart->delete();
        }
      }
      foreach ($goods as $pid => $quantity) {
        $this->addItem($pid, $quantity);
      }
    }
  }

  /**
   * Собрать массив данных о текущей корзине для страницы корзины.
   */
  public function renderCartPageInfo($render = []) {
    $render['quantity'] = 0;
    if (is_object($this->cart)) {
      $items = $this->cart->getItems();
      foreach ($items as $index => $orderItem) {
        $render['id'] = $this->cart->id();
        $render['items'][$orderItem->id()] = $this->getVariationInfo($orderItem);
        $render['quantity'] += $orderItem->getQuantity();
      }
    }
    $render['currency'] = !empty($render['items']) ? reset($render['items'])['currency'] : '';
    $render['total'] = !empty($items) ? $this->cart->getTotalPrice()->getNumber() : 0;
    return $render;
  }

  /**
   * Получить информацию о вариации из товара в корзине.
   */
  private function getVariationInfo($orderItem, $info = []) {
    $currency_storage = \Drupal::entityTypeManager()->getStorage('commerce_currency');
    if (is_object($variation = $orderItem->getPurchasedEntity())) {
      $this->getVariationProductInfo($variation, $info);
      $currencyCode = $variation->getPrice()->getCurrencyCode();
      $info['id'] = $orderItem->id();
      $info['quantity'] = round($orderItem->getQuantity());
      $info['stock'] = intval($variation->field_stock->value);
      $info['price'] = $variation->getPrice()->getNumber();
      $info['totalPrice'] = $orderItem->getTotalPrice()->getNumber();
      $info['currency'] = $currency_storage->load($currencyCode)->getSymbol();
    }
    return $info;
  }

  /**
   * Получить информацию о товаре вариации.
   */
  private function getVariationProductInfo($variation, &$info) {
    if (is_object($product = $variation->getProduct())) {
      $title = $product->field_title->value;
      $info['title'] = $title ? $title : $product->getTitle();
      $info['picture'] = $this->getProductPicture($product);
      $info['url'] = $product->url();
      $info['pid'] = $product->id();
    }
  }

  /**
   * Загрузка изображения товара.
   */
  private function getProductPicture($product) {
    $image = '';
    $fieldGallery = $product->field_gallery;
    if (!$fieldGallery->isEmpty()) {
      foreach ($fieldGallery as $item) {
        $imageUri = $item->entity->getFileUri();
        $image = ImageStyle::load('product_cart')->buildUrl($imageUri);
        break;
      }
    }
    return $image;
  }

  /**
   * Перевод корзины в статус заказа.
   */
  public function cartToOrder() {
    $this->cart->set('state', 'completed');
    //$this->cart->set('placed', time());
    $this->cart->save();
    return $this->cart;
  }

  /**
   * Создать новую корзину.
   */
  private function createCart() {
    $account = \Drupal::currentUser();
    $store = Store::load(1);
    $this->cart = $this->cartProvider->createCart('default', $store, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $items = $this->cart->getItems();
    return empty($items);
  }

  /**
   * {@inheritdoc}
   */
  public function sendReceipt($order) {
    /** @var \Drupal\commerce\Entity\MailHandler $mail_handler */
    $mail_handler = \Drupal::service('commerce.mail_handler');
    /** @var \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary */
    $order_total_summary = \Drupal::service('commerce_order.order_total_summary');
    $order_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $to = $order->getEmail();
    if (!$to) {
      // The email should not be empty, unless the order is malformed.
      return;
    }

    $subject = t('Order #@number confirmed', ['@number' => $order->getOrderNumber()]);
    $body = [
      '#theme' => 'commerce_order_receipt',
      '#order_entity' => $order,
      '#totals' => $order_total_summary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $profile_view_builder = \Drupal::entityTypeManager()->getViewBuilder('profile');;
      $body['#billing_information'] = $profile_view_builder->view($billing_profile);
    }
    $params = [
      'id' => 'order_receipt',
      'from' => $order->getStore()->getEmail(),
      'bcc' => $order_type->getReceiptBcc(),
      'order' => $order,
    ];
    $customer = $order->getCustomer();
    if ($customer->isAuthenticated()) {
      $params['langcode'] = $customer->getPreferredLangcode();
    }
    $mail_handler->sendMail($to, $subject, $body, $params);
  }

}
