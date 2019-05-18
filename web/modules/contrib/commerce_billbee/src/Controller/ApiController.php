<?php

namespace Drupal\commerce_billbee\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ApiController.
 */
class ApiController extends ControllerBase {

  /**
   * Billbee API endpoint.
   */
  public function endpoint() {

    $action = \Drupal::request()->query->get('Action', FALSE);

    $enable_logging = \Drupal::config('commerce_billbee.settings')
      ->get('enable_logging');
    if ($enable_logging) {
      \Drupal::logger('commerce_billbee')
        ->notice("Billbee log - Action: %action - GET params '%get' - POST params '%post'", [
          '%action' => $action,
          '%get' => json_encode(\Drupal::request()->query->all()),
          '%post' => json_encode(\Drupal::request()->request->all()),
        ]);
    }
    switch ($action) {
      case 'GetProduct':
        $product_id = \Drupal::request()->query->get('ProductId', FALSE);
        return new JsonResponse($this->getProduct($product_id));
      case 'GetProducts':
        return new JsonResponse($this->getProducts());
      case 'GetOrder':
        $order_id = \Drupal::request()->query->get('OrderId', FALSE);
        return new JsonResponse($this->getOrder($order_id));
      case 'GetOrders':
        return new JsonResponse($this->getOrders());
      case 'SetStock':
        return new JsonResponse($this->setStock());
      case 'SetOrderState':
        return new JsonResponse($this->setOrderState());
      case 'AckOrder':
        return new JsonResponse($this->ackOrder());
    }

    throw new BadRequestHttpException('Action not supported.');
  }

  /**
   * Handles the GetProduct action.
   *
   * @see hook_commerce_billbee_product_alter(()
   */
  private function getProduct($product_id) {

    // Ensure request product variation exists.
    $commerce_product_variation = ProductVariation::load($product_id);
    if (!$commerce_product_variation) {
      throw new NotFoundHttpException('Commerce product variation does not exist.');
    }

    $response = [];

    $response['id'] = (int) $commerce_product_variation->id();
    $response['title'] = $commerce_product_variation->getTitle();
    $response['sku'] = $commerce_product_variation->getSku();
    $response['price'] = $commerce_product_variation->getPrice()->getNumber();
    $response['description'] = $commerce_product_variation->getTitle();

    $stock_service_manager = \Drupal::service('commerce_stock.service_manager');
    $drupal_stock = $stock_service_manager->getStockLevel($commerce_product_variation);
    $response['quantity'] = $drupal_stock;

    $response['weight'] = 0;
    if ($commerce_product_variation->weight) {
      $measurement_item = $commerce_product_variation->weight->first();
      if($measurement_item){
        $measurement = $measurement_item->toMeasurement();
        $response['weight'] = $measurement->convert('kg')->getNumber();
      }
    }

    // TODO: implement commerce vat here.
    $response['vat_rate'] = 19.0000;

    // Retrieve image(s) for product variation.
    $response['images'] = [];
    $image_field = \Drupal::config('commerce_billbee.settings')
      ->get('image_field');
    if ($image_field && $image_field != '_none' && !$commerce_product_variation->{$image_field}->isEmpty()) {
      $values = $commerce_product_variation->{$image_field}->getValue();
      foreach ($values as $index => $value) {
        $file = File::load($value['target_id']);
        $uri = $file->uri->value;
        $response['images'][] = [
          'url' => file_create_url($uri),
          'isDefault' => $index == 0 ? TRUE : FALSE,
          'position' => ($index + 1),
        ];
      }
    }

    \Drupal::moduleHandler()
      ->alter('commerce_billbee_product', $response, $commerce_product_variation);
    return $response;
  }

  /**
   * Handles the GetProducts action.
   */
  private function getProducts() {
    $response = [];

    $total = \Drupal::entityQuery('commerce_product_variation')
      ->count()
      ->execute();

    // Handle paging.
    $page = abs((int) \Drupal::request()->query->get('Page', 1));
    $page_size = abs((int) \Drupal::request()->query->get('PageSize', 100));
    // For now, maximum requested products at once hardcoded to 500.
    if ($page_size > 500) {
      $page_size = 500;
    }
    $start = ($page * $page_size) - $page_size;
    $response['paging'] = [
      'page' => $page,
      'totalCount' => $total,
      'totalPages' => ceil($total / $page_size),
    ];

    // Query requested range and output products for it.
    $products = [];
    $product_variation_ids = \Drupal::entityQuery('commerce_product_variation')
      ->range($start, $page_size)
      ->execute();
    foreach ($product_variation_ids as $product_variation_id) {
      $products[] = $this->getProduct($product_variation_id);
    }
    $response['products'] = $products;

    return $response;
  }

  /**
   * Handles the GetOrder action.
   *
   * @see hook_commerce_billbee_order_alter(()
   */
  private function getOrder($order_id) {

    // Ensure request order exists.
    $commerce_order = Order::load($order_id);
    if (!$commerce_order) {
      throw new NotFoundHttpException('Commerce order does not exist.');
    }

    $response = [];

    $order_total_currency_code = $commerce_order->getTotalPrice()
      ->getCurrencyCode();
    $response['order_id'] = (int) $commerce_order->id();
    $response['order_number'] = $commerce_order->id();
    $response['customer_id'] = $commerce_order->getCustomerId();
    $response['email'] = $commerce_order->getEmail();
    $response['phone1'] = NULL; // @see hook_commerce_billbee_order_alter().
    $response['phone2'] = NULL; // @see hook_commerce_billbee_order_alter().
    $response['fax'] = ""; // @see hook_commerce_billbee_order_alter().
    $response['vat_id'] = "BE182 3832 283"; // TODO: integrate with commerce vat
    $response['vat_mode'] = 0; // TODO: Documentation says "see below", cant find any more info though.

    /** @var  $billing_profile \Drupal\profile\Entity\ProfileInterface */
    $billing_profile = $commerce_order->getBillingProfile();
    $billing_address = $billing_profile->get('address')->first();
    $response['invoice_address'] = [];
    $response['invoice_address']['firstname'] = $billing_address->getGivenName();
    $response['invoice_address']['name2'] = $billing_address->getAdditionalName();
    $response['invoice_address']['lastname'] = $billing_address->getFamilyName();
    $response['invoice_address']['company'] = $billing_address->getOrganization();
    $splitted_addressline1 = $this->splitAddressLine1($billing_address->getAddressLine1());
    $response['invoice_address']['street'] = $splitted_addressline1['street'];
    $response['invoice_address']['housenumber'] = $splitted_addressline1['housenumber'];
    $response['invoice_address']['address2'] = $billing_address->getAddressLine2();
    $response['invoice_address']['city'] = $billing_address->getLocality();
    $response['invoice_address']['postcode'] = $billing_address->getPostalCode();
    $response['invoice_address']['country_code'] = $billing_address->getCountryCode();
    $response['invoice_address']['state'] = $billing_address->getDependentLocality();

    $shipments = $commerce_order->shipments->referencedEntities();
    /** @var \Drupal\commerce_shipping\Entity|ShipmentInterface $shipment */
    $shipment = reset($shipments);
    if ($shipment && $shipment->getShippingProfile()) {
      $shipping_address = $shipment->getShippingProfile()
        ->get('address')
        ->first();
      $response['delivery_address'] = [];
      $response['delivery_address']['firstname'] = $shipping_address->getGivenName();
      $response['delivery_address']['name2'] = $shipping_address->getAdditionalName();
      $response['delivery_address']['lastname'] = $shipping_address->getFamilyName();
      $response['delivery_address']['company'] = $shipping_address->getOrganization();
      $splitted_addressline1 = $this->splitAddressLine1($shipping_address->getAddressLine1());
      $response['delivery_address']['street'] = $splitted_addressline1['street'];
      $response['delivery_address']['housenumber'] = $splitted_addressline1['housenumber'];
      $response['delivery_address']['address2'] = $shipping_address->getAddressLine2();
      $response['delivery_address']['city'] = $shipping_address->getLocality();
      $response['delivery_address']['postcode'] = $shipping_address->getPostalCode();
      $response['delivery_address']['country_code'] = $shipping_address->getCountryCode();
      $response['delivery_address']['state'] = $shipping_address->getDependentLocality();
    }


    $response['payment_method'] = 1; // = bank transfer, we prolly need to provide a hook to allow altering this depending on the used payment provider.
    $response['order_status_id'] = 1; // TODO: For now, we use ORDERED as state
    $response['currency_code'] = $order_total_currency_code;
    $response['order_date'] = date('Y-m-d H:i:s', $commerce_order->getCreatedTime());

    $response['pay_date'] = NULL;
    $payments = \Drupal::entityTypeManager()->getStorage('commerce_payment')->loadByProperties(['order_id' => $commerce_order->id()]);
    if($payments){
      $last_payment = end($payments);
      if($last_payment->getCompletedTime()){
        $response['pay_date'] = date('Y-m-d H:i:s', $last_payment->getCompletedTime());
      }
    }

    $response['ship_date'] = NULL; // Format similar to above, however, Drupal Commerce does not trigger shipping date for now, maybe we can see if an order state is available?

    // Calculate shipping cost total.
    $shipping_price = new Price('0', $order_total_currency_code);
    foreach ($commerce_order->getAdjustments() as $adjustment) {
      if ($adjustment->getType() == 'shipping') {
        $shipping_price = $shipping_price->add($adjustment->getAmount());
      }
    }
    $response['ship_cost'] = $shipping_price->getNumber();

    // Initialize promotion price for later use.
    $promotion_price = new Price('0', $order_total_currency_code);

    // Render order lines.
    $response['order_products'] = [];
    $order_items = $commerce_order->getItems();
    foreach ($order_items as $order_item) {
      //TODO: For now we only support order lines attached to an entity (Product variant), where the attached product variant has not been deleted.
      if ($order_item->hasPurchasedEntity() && $purchased_entity = $order_item->getPurchasedEntity()) {
        $order_product = [];
        $order_product['product_id'] = $purchased_entity->id();
        $order_product['name'] = $purchased_entity->label();
        $order_product['sku'] = $purchased_entity->getSku();
        $order_product['quantity'] = $order_item->getQuantity();
        $order_product['unit_price'] = $order_item->getUnitPrice()->getNumber();

        foreach ($order_item->getAdjustments() as $adjustment) {
          if ($adjustment->getType() == 'promotion') {
            $order_item_total_promotion_adjustment = $adjustment->multiply($order_item->getQuantity());
            $promotion_price = $promotion_price->add($order_item_total_promotion_adjustment->getAmount());
          }
          if ($adjustment->getType() == 'tax') {
            $order_product['tax_rate'] = $adjustment->getPercentage() * 100;
          }
        }

        $order_product['options'] = [];
        $attributes = $purchased_entity->getAttributeValues();
        foreach ($attributes as $attribute) {

          $order_product['options'][] = [
            'name' => $attribute->getAttributeId(),
            'value' => $attribute->getName(),
          ];
        }
        // Add product to collection.
        $response['order_products'][] = $order_product;
      }
    }

    // Calculate promotion adjustments on order.
    foreach ($commerce_order->getAdjustments() as $adjustment) {
      if ($adjustment->getType() == 'promotion') {
        $promotion_price = $promotion_price->add($adjustment->getAmount());
      }
    }
    // If promotion is other value as 0, add it as custom order_product line.
    if($promotion_price->getNumber() != 0){
      $promotion_product = [];
      $promotion_product['quantity'] = "1.00";
      $promotion_product['unit_price'] = $promotion_price->getNumber();
      $promotion_product['name'] = t('Discount');
      $response['order_products'][] = $promotion_product;
    }

    $response['order_history'] = []; // TODO: Optional: history of the order, order revision usable for this prolly?

    // Allow other modules to alter the order response.
    \Drupal::moduleHandler()
      ->alter('commerce_billbee_order', $response, $commerce_order);

    return $response;
  }

  /**
   * Handles the GetOrders action.
   */
  private function getOrders() {
    $response = [];

    $total = \Drupal::entityQuery('commerce_order')
      ->condition('state', ['completed', 'fulfillment'], 'IN')
      ->condition('billbee_ack', FALSE)
      ->count()
      ->execute();

    // Handle paging.
    $page = abs((int) \Drupal::request()->query->get('Page', 1));
    $page_size = abs((int) \Drupal::request()->query->get('PageSize', 100));
    // For now, maximum requested products at once hardcoded to 500.
    if ($page_size > 500) {
      $page_size = 500;
    }
    $start = ($page * $page_size) - $page_size;
    $response['paging'] = [
      'page' => $page,
      'totalCount' => $total,
      'totalPages' => ceil($total / $page_size),
    ];

    // Query requested range and output orders for it.
    $orders = [];
    $order_ids = \Drupal::entityQuery('commerce_order')
      ->condition('state', ['completed', 'fulfillment'], 'IN')
      ->condition('billbee_ack', FALSE)
      ->sort('order_id', 'DESC')
      ->range($start, $page_size)
      ->execute();
    foreach ($order_ids as $order_id) {
      $orders[] = $this->getOrder($order_id);
    }
    $response['orders'] = $orders;

    return $response;
  }

  /**
   * Handles the SetStock action.
   */
  private function setStock() {
    $product_id = \Drupal::request()->request->get('ProductId', FALSE);
    // Ensure request product variation exists.
    $commerce_product_variation = ProductVariation::load($product_id);
    if (!$commerce_product_variation) {
      throw new NotFoundHttpException('Commerce product variation does not exist.');
    }
    $billbee_stock = (int) \Drupal::request()->request->get('AvailableStock', -1);
    if ($billbee_stock < 0) {
      throw new NotFoundHttpException('Stock quantity can not be less than 0.');
    }

    $stock_service_manager = \Drupal::service('commerce_stock.service_manager');
    $drupal_stock = $stock_service_manager->getStockLevel($commerce_product_variation);
    $transaction_qty = $billbee_stock - $drupal_stock;

    // If there is a change in stock, update local storage.
    if ($transaction_qty) {
      // Code below based on \Drupal\commerce_stock_field\Plugin\Field\FieldType\StockLevel
      $transaction_type = ($transaction_qty > 0) ? StockTransactionsInterface::STOCK_IN : StockTransactionsInterface::STOCK_OUT;
      /** @var \Drupal\commerce_stock\StockLocationInterface $location */
      $location = $stock_service_manager->getTransactionLocation($stock_service_manager->getContext($commerce_product_variation), $commerce_product_variation, $transaction_qty);
      if (empty($location)) {
        // This should never get called as we should always have a location.
        return;
      }
      $zone = '';
      $unit_cost = NULL;
      $metadata = ['data' => ['message' => 'stock level updated by Billbee']];
      $stock_service_manager->createTransaction($commerce_product_variation, $location->getId(), $zone, $transaction_qty, $unit_cost, $transaction_type, $metadata);

    }
  }

  /**
   * Handles the SetOrderState action.
   */
  private function setOrderState() {

    $order_id = \Drupal::request()->request->get('OrderId', FALSE);

    // Ensure request order exists.
    $commerce_order = Order::load($order_id);
    if (!$commerce_order) {
      throw new NotFoundHttpException('Commerce order does not exist.');
    }

    // Available parameters which we do not use at the moment.
    //    $date = \Drupal::request()->request->get('Date', FALSE);
    //    $tracking_code = \Drupal::request()->request->get('TrackingCode', FALSE);
    //    // The Comment parameter is optional and is only sent, when you have a rule
    //    // in the Billbee Account that tries to send a message via the shop api on
    //    // an order state change event.
    //    $comment = \Drupal::request()->request->get('Comment', FALSE);

    $billbee_state_mapping = [
      'Zahlung_erhalten' => 'paid',
      'Versendet' => 'shipped',
      'Abgeschlossen' => 'closed',
      'Storniert' => 'canceled',
    ];

    $new_state = \Drupal::request()->request->get('NewStateId', FALSE);

    if (!array_key_exists($new_state, $billbee_state_mapping)) {
      \Drupal::logger('commerce_billbee')
        ->error("Billbee state not recognised: %state", ['%state' => $new_state]);
      throw new \InvalidArgumentException('Billbee state not recognised.');
    }

    // Trigger correct order transition.
    switch($billbee_state_mapping[$new_state]){
      case 'shipped':
        $transitions = $commerce_order->getState()->getTransitions();
        if(isset($transitions['fulfill'])){
          $commerce_order->getState()->applyTransition($transitions['fulfill']);
          $commerce_order->save();
        }
        break;
      case 'canceled':
        $transitions = $commerce_order->getState()->getTransitions();
        if(isset($transitions['cancel'])){
          $commerce_order->getState()->applyTransition($transitions['cancel']);
          $commerce_order->save();
        }
      case 'paid':
        $payments = \Drupal::entityTypeManager()->getStorage('commerce_payment')->loadByProperties(['order_id' => $commerce_order->id()]);
        if($payments){
          $last_payment = end($payments);
          $last_payment->state = 'completed';
          $last_payment->setAmount($last_payment->getAmount());
          $last_payment->save();
        }
        break;
    }
  }

  /**
   * Handles the AckOrder action.
   */
  private function ackOrder() {
    // Store that an order is in sync with Billbee to reduce the number of
    // orders returned by the GetOrders action.
    $order_id = \Drupal::request()->request->get('OrderId', FALSE);
    // Ensure request order exists.
    $commerce_order = Order::load($order_id);
    if (!$commerce_order) {
      throw new NotFoundHttpException('Commerce order does not exist.');
    }
    $commerce_order->billbee_ack = TRUE;
    $commerce_order->save();
  }

  /**
   * Checks endpoint access based on API key in request.
   */
  public function endpoint_access() {

    $settings = \Drupal::config('commerce_billbee.settings');
    $skip_authentication = $settings->get('skip_authentication');
    if ($skip_authentication) {
      return AccessResult::allowed();
    }

    $pwd = $settings->get('api_key');
    if (!$pwd) {
      return AccessResult::forbidden('API key is not defined in Drupal for Commerce Billbee integration.');
    }
    $key = \Drupal::request()->query->get('Key', FALSE);
    if (!$key) {
      return AccessResult::forbidden('No API key provided.');
    }

    // Implement Billbee encryption as documented in Billbee Shop API.
    $unixtimestamp = substr(time(), 0, 7);
    $hash = hash_hmac("sha256", utf8_encode($pwd), utf8_encode($unixtimestamp));
    $bsec = base64_encode($hash);
    $bsec = str_replace("=", "", $bsec);
    $bsec = str_replace("/", "", $bsec);
    $bsec = str_replace("+", "", $bsec);
    if ($bsec !== $key) {
      return AccessResult::forbidden('Incorrect API key.');
    }

    return AccessResult::allowed();
  }

  /**
   * Helper function which splits street and number into 2 separate fields.
   * If fail, it will return the given address line as street and empty
   * housenumber.
   */
  private function splitAddressLine1($address_line_1) {
    $output = ['street', $address_line_1, 'housenumber' => ''];
    // Retrieve street and housenumber from addressline1 (hacky, better would be to store it in separate fields in Address).
    if (preg_match('/([^\d]+)\s?(.+)/i', $address_line_1, $result)) {
      $output['street'] = $result[1];
      $output['housenumber'] = $result[2];
    }
    return $output;
  }

}
