<?php

namespace Drupal\cmlexchange\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use DOMDocument;
use SimpleXMLElement;

/**
 * CommerceML Orders service.
 */
class Orders implements OrdersInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DebugML object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Query.
   */
  public function xml($from = FALSE) {
    // TODO: change query.
    if (!$from) {
      $from = strtotime('now -2 week');
    }
    $orders = [];
    if (\Drupal::moduleHandler()->moduleExists('commerce_order')) {
      $orders = $this->query($from);
      \Drupal::moduleHandler()->alter('cmlexchange_orders_query', $orders);
    }
    // Get Simples XML.
    $xml = '<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация/>';
    if (!empty($orders)) {
      $xml = $this->getXml($orders);
      \Drupal::moduleHandler()->alter('cmlexchange_orders_xml', $xml, $orders);
    }
    // Format XML.
    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = FALSE;
    $dom->formatOutput = TRUE;
    $dom->loadXML($xml);
    $dom_xml = $dom->saveXML();
    return $dom_xml;
  }

  /**
   * Query.
   *
   * Use HOOK_cmlexchange_orders_query_alter(&$orders){} for changes.
   */
  public function query($created = 0, $count = FALSE) {
    $entities = [];
    $entity_type = 'commerce_order';
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('cart', 0)
      ->condition('created', $created, '<')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    if ($count) {
      $query->range(0, $count);
    }
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Create XML.
   *
   * Use HOOK_cmlexchange_orders_xml_alter(&$xml, $orders){} for changes.
   */
  public function getXml($orders) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация/>');
    $xml->addAttribute('ВерсияСхемы', '2.09');
    $date = format_date(time(), 'custom', 'Y-m-d');
    $xml->addAttribute('ДатаФормирования', $date);

    $query = \Drupal::entityQuery('commerce_order')
      // TODO: ->condition('field_order_unloaded', 0)
      ->condition('cart', 0);
    $result = $query->execute();

    if (!empty($orders)) {
      foreach ($orders as $order_id => $order) {
        $document = $xml->addChild('Документ');
        $document->addChild('Ид', $order_id);
        $document->addChild('Номер', $order_id);
        $document->addChild('Дата', format_date($order->getCompletedTime(), 'custom', 'Y-m-d'));
        $document->addChild('Время', format_date($order->getCompletedTime(), 'custom', 'H:i:s'));
        $document->addChild('Валюта', $order->total_price->currency_code);
        $document->addChild('Сумма', $order->total_price->number);
        $document->addChild('ХозОперация', 'Заказ товара');

        $contragents = $document->addChild('Контрагенты');
        $contragent = $contragents->addChild('Контрагент');
        $this->userData($contragent, $order);

        $theGoods = $document->addChild('Товары');

        $orderItemsObj = $order->order_items;

        for ($orderItemPlace = 0; $orderItemPlace < $orderItemsObj->count(); $orderItemPlace++) {
          foreach ($orderItemsObj->get($orderItemPlace)->getValue() as $key => $itemId) {
            $orderItem = \Drupal::entityManager()->getStorage('commerce_order_item')->load($itemId);
            $offer = $orderItem->getPurchasedEntity();
            $goods = $theGoods->addChild('Товар');
            $goods->addChild('Ид', $offer->getSku());
            $goods->addChild('Наименование', $offer->getTitle());
            //$goods->addChild('ЦенаЗаЕдиницу', $orderItem->getUnitPrice());
            $goods->addChild('ЦенаЗаЕдиницу', $orderItem->unit_price->number);
            $goods->addChild('Количество', $orderItem->getQuantity());
            //$goods->addChild('Сумма', $orderItem->getTotalPrice());
            $goods->addChild('Сумма', $orderItem->total_price->number);
          }
        }
      }
    }
    return $xml->asXML();
  }

  /**
   * Формируем данные из профиля.
   */
  public function userData(&$element, $order) {
    $profile = $order->getBillingProfile();
    $name = isset($profile->field_customer_fie->value) ? $profile->field_customer_fie->value : '';
    $phone = isset($profile->field_customer_phone->value) ? $profile->field_customer_phone->value : '';
    $city = isset($profile->field_city->value) ? $profile->field_city->value : '';
    $street = isset($profile->field_street->value) ? $profile->field_street->value : '';
    $house = isset($profile->field_house->value) ? $profile->field_house->value : '';
    $apartment = isset($profile->field_apartment->value) ? $profile->field_apartment->value : '';
    $postcode = isset($profile->field_postcode->value) ? $profile->field_postcode->value : '';
    $email = isset($profile->field_customer_email->value) ? $profile->field_customer_email->value : '';

    $element->addChild('Наименование', $name);
    $element->addChild('Роль', 'Покупатель');
    $element->addChild('ПолноеНаименование', $name);
    $element->addChild('Телефон', $phone);
    $element->addChild('Город', $city);
    $element->addChild('Улица', $street);
    $element->addChild('Дом', $house);
    $element->addChild('Квартира', $apartment);
    $element->addChild('Индекс', $postcode);
    $element->addChild('ЭлектроннаяПочта', $email);
  }

}
