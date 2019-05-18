<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_verifone\DependencyInjection;

use Drupal\Core\Database\Database;

class OrderLockerHelper
{
  const TABLE_NAME = 'commerce_verifone_order_process_status';

  public function lockOrder($orderId)
  {
    $result = false;

    $connection = Database::getConnection();

    try {
      $connection->startTransaction();

      $query = $connection->select($this->_getTableName())
        ->condition('order_id', $orderId, '=');

      if($query->countQuery()->execute()->fetchField()) {
        $result = (bool)$connection->update($this->_getTableName())
          ->fields(['under_process' => 1])
          ->condition('under_process', 0, '0')
          ->condition('order_id', $orderId, '0')
          ->execute();
      } else {
        $result = (bool)$connection->insert($this->_getTableName())->fields(['order_id' => $orderId, 'under_process' => 1])->execute();
      }

    } catch (\Exception $e) {
      try {
        $connection->rollBack();
      } catch (\Exception $e) {
        return false;
      }
    }

    return $result;
  }

  public function unlockOrder($orderId)
  {
    $result = false;

    $connection = Database::getConnection();

    try {
      $connection->startTransaction();

      $result = (bool)$connection->update($this->_getTableName())
        ->fields(['under_process' => 0])
        ->condition('under_process', 1, '0')
        ->condition('order_id', $orderId, '0')
        ->execute();

    } catch (\Exception $e) {
      try {
        $connection->rollBack();
      } catch (\Exception $e) {
        return false;
      }
    }

    return $result;
  }

  public function isLockedOrder($orderId)
  {

    $connection = Database::getConnection();

    try {
      $query = $connection->select($this->_getTableName())
        ->condition('under_process', 1, '=')
        ->condition('order_id', $orderId, '=');
    } catch (\Exception $e) {
      return false;
    }

    if($query->countQuery()->execute()->fetchField()) {
      return true;
    }

    return false;

  }

  protected function _getTableName()
  {
    return self::TABLE_NAME;
  }
}