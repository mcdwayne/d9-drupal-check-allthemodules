<?php

namespace Drupal\syncart\Service;

/**
 * Interface SynCartServiceInterface.
 */
interface SynCartServiceInterface {

  /**
   * Загрузка текущей корзины пользователя.
   *
   * @return object
   *   Объект корзины.
   */
  public function load();

  /**
   * Добавить вариацию в корзину.
   *
   * @var $vid
   *   Id вариации.
   *
   * @var $quantity
   *   Кол-во добавляемых в корзину товаров.
   */
  public function addItem($vid, $quantity = 1);

  /**
   * Добавить вариацию в корзину.
   *
   * @var $orderItemId
   *   Id вариации.
   */
  public function removeOrderItem($orderItemId);

  /**
   * Подготовка данных для странциы корзины.
   *
   * @return array
   *   Структура корзины
   */
  public function renderCartPageInfo();

  /**
   * Проверка наличия товаров в корзине.
   *
   * @return bool
   *   Пуста ли корзина
   */
  public function isEmpty();

  /**
   * Отправляем чек клиенту на почтку.
   *
   * @var \Drupal\commerce_order\Entity\Order $order
   *
   */
  public function sendReceipt($order);

}
