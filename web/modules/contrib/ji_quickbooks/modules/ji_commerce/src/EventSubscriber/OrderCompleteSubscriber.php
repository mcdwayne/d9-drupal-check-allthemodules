<?php

namespace Drupal\ji_commerce\EventSubscriber;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use QuickBooksOnline\API\Data\IPPDiscountLineDetail;
use QuickBooksOnline\API\Data\IPPLine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ji_quickbooks\JIQuickBooksService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\user\Entity\User;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;

/**
 * Class OrderCompleteSubscriber.
 *
 * @package Drupal\ji_commerce
 */
class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = [
      'sendQuickBooksData',
      -100,
    ];
    $events[KernelEvents::REQUEST] = ['checkForRedirection'];
    return $events;
  }

  /**
   * Used for testing. Called on every page load.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    //    $percentage_rate = [];
    //    $tax_type_storage = \Drupal::entityTypeManager()
    //      ->getStorage('commerce_tax_type');
    //    $tax_types = $tax_type_storage->loadMultiple();
    //    /** @var \Drupal\commerce_tax\Entity\TaxType $tax_type */
    //    foreach ($tax_types as $id => $tax_type) {
    //      if (strpos($id, 'quickbooks_tax_id_') !== FALSE) {
    //        $configuration = $tax_type->getPluginConfiguration();
    //        $rates = $configuration['rate'];
    //        foreach ($rates as $rate) {
    //          if (!isset($percentage_rate[$id])) {
    //            $percentage_rate[$id] = floatval($rate['percentage']);
    //          }
    //          else {
    //            $percentage_rate[$id] += floatval($rate['percentage']);
    //          }
    //        }
    //      }
    //    }
    //    $t = '';
    //    $order = Order::load(1450);
    //    $items = $order->getItems();
    //    foreach ($items as $item) {
    //      $t = '';
    //      $current_adjustments = $item->getAdjustments();
    //      foreach ($current_adjustments as $adjustment) {
    //        $item->removeAdjustment($adjustment);
    //      }
    //
    //      $t = '';
    //      $item->addAdjustment(new Adjustment([
    //        'type' => 'tax',
    //        'label' => 'Tax',
    //        'amount' => new Price('15', 'USD'),
    //        'percentage' => '1',
    //        'source_id' => 'quickbooks_tax_id_20|default|53',
    //        'included' => FALSE,
    //      ]));
    //      $item->save();
    //    }
    //    $container = \Drupal::getContainer();
    //    $kernel = $container->get('kernel');
    //    $services = $kernel->getCachedContainerDefinition()['services'];
    //    if (isset($services['commerce_tax.tax_order_processor'])) {
    //      $tax = $services['commerce_tax.tax_order_processor'];
    //      $unserialized = unserialize($tax);
    //      $t = '';
    //    }
    //    foreach ($services as $service_id => $value) {
    //      $service_definition = unserialize($value);
    //    }
    // /** @var \Drupal\commerce_tax\TaxOrderProcessor $service */
    //    $service = \Drupal::service('commerce_tax.tax_order_processor');
    //    $service->process();
    //    $t = '';

    //    $order = \Drupal\commerce_order\Entity\Order::load(373);
    //    $line_item = [];
    //    $adjustments = $order->getAdjustments();
    //    foreach ($adjustments as $adjustment) {
    //      $this->prepareAdjustments($adjustment, $line_item);
    //    }
    //    $t = '';
  }

  //  private function prepareAdjustments($adjustment, &$line_item) {
  //    // Don't allow more than one promotion since QBO
  //    // will complain.
  //    //    if (count($_adjustments)) {
  //    //      return;
  //    //    }
  //
  //    $type = $adjustment->getType();
  //    switch ($type) {
  //      case 'custom':
  //      case 'fee':
  //      case 'promotion':
  //        $percentage = $adjustment->getPercentage();
  //        // If our promotion is a percentage base, else it's an assigned number.
  //        if (isset($percentage)) {
  //          $promotion = new IPPLine();
  //          $promotion->DetailType = 'DiscountLineDetail';
  //          $discount_line_detail = new IPPDiscountLineDetail();
  //          $discount_account = \Drupal::state()
  //            ->get('ji_quickbooks_discount_account');
  //          $discount_line_detail->DiscountAccountRef = $discount_account;
  //          $discount_line_detail->DiscountPercent = $adjustment->getPercentage() * 100;
  //          $discount_line_detail->PercentBased = 'true';
  //          $promotion->DiscountLineDetail = $discount_line_detail;
  //          $line_item[] = $promotion;
  //        }
  //        else {
  //          /** @var \Drupal\commerce_price\Price $amount */
  //          $amount = $adjustment->getAmount();
  //          $promotion = new IPPLine();
  //          $promotion->Amount = abs($amount->getNumber());
  //          $promotion->DetailType = 'DiscountLineDetail';
  //
  //          $discount_line_detail = new IPPDiscountLineDetail();
  //          $discount_account = \Drupal::state()
  //            ->get('ji_quickbooks_discount_account');
  //          $discount_line_detail->DiscountAccountRef = $discount_account;
  //          // Make sure we return a positive number. QBO knows to subtract this
  //          // amount.
  //          $discount_line_detail->PercentBased = 'false';
  //          $promotion->DiscountLineDetail = $discount_line_detail;
  //          $line_item[] = $promotion;
  //        }
  //        break;
  //
  //      case 'shipping':
  //        break;
  //    }
  //  }

  /**
   * Event callback.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function sendQuickBooksData(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $event->getEntity();

    if ($order instanceof \Drupal\commerce_order\Entity\Order) {
      $uid = $order->get('uid')->getString();

      $account = User::load($uid);

      try {
        $quickbooks_service = new JIQuickBooksService(FALSE);
        // Avoid crashing the checkout process.
        if (isset($quickbooks_service) && $quickbooks_service->settingErrorMessage) {
          \Drupal::logger('JI QuickBooks sendQuickBooksData')
            ->error($quickbooks_service->settingErrorMessage);
          return;
        }

        $qbo_customer_id = $quickbooks_service->sendCustomer($order, $account);
        if ($qbo_customer_id) {
          $qbo_invoice_id = $quickbooks_service->sendInvoice($order, $qbo_customer_id);
          if ($qbo_invoice_id) {
            $quickbooks_service->sendPayment($order, $qbo_customer_id, $qbo_invoice_id);
          }
        }
      } catch (\Exception $e) {
        \Drupal::logger('OrderCompleteSubscriber error')
          ->error($e->getMessage());
      }
    }
  }

}
