<?php

namespace Drupal\odoo_api\OdooApi\Data;

use Drupal\odoo_api\OdooApi\ClientInterface;

/**
 * Order Nuker service implementation.
 */
class OrderNuker implements OrderNukerInterface {

  /**
   * Odoo API client.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   */
  protected $api;

  /**
   * Constructs a new OrderNuker object.
   *
   * @param \Drupal\odoo_api\OdooApi\ClientInterface $api
   *   Odoo API client service.
   */
  public function __construct(ClientInterface $api) {
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public function nuke($order_id) {
    foreach ($this->getStockPickingIds($order_id) as $stock_picking_id) {
      $this->cancelStockPicking($stock_picking_id);
    }
    $this->cancelOrder($order_id);
    $this->deleteOrder($order_id);
  }

  /**
   * Get IDs of stock pickings for a given order.
   *
   * @param int $order_id
   *   Odoo order ID.
   *
   * @return int[]
   *   Array of stock.picking model IDs.
   */
  protected function getStockPickingIds($order_id) {
    $filter = [
      [
        'sale_id',
        '=',
        $order_id,
      ],
    ];

    return $this->api->search('stock.picking', $filter);
  }

  /**
   * Cancel stock.picking.
   *
   * @param int $stock_picking_id
   *   Odoo stock.picking model object ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\ClientException
   *   Odoo API client exceptions.
   */
  protected function cancelStockPicking($stock_picking_id) {
    $move_line_ids = $this->api
      ->read('stock.picking', [$stock_picking_id], ['move_line_ids'])[0]['move_line_ids'];
    $this->api->write('stock.move.line', $move_line_ids, ['state' => 'draft']);
    $this->api->unlink('stock.move.line', $move_line_ids);
    $this->api->write('stock.picking', [$stock_picking_id], ['state' => 'draft']);
    $this->api->rawModelApiCall('stock.picking', 'action_cancel', [$stock_picking_id]);
  }

  /**
   * Cancel the order.
   *
   * @param int $order_id
   *   Odoo sale.order model object ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\ClientException
   *   Odoo API client exceptions.
   */
  protected function cancelOrder($order_id) {
    $this
      ->api
      ->rawModelApiCall('sale.order', 'action_cancel', [$order_id]);
  }

  /**
   * Delete the order.
   *
   * @param int $order_id
   *   Odoo sale.order model object ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\ClientException
   *   Odoo API client exceptions.
   */
  protected function deleteOrder($order_id) {
    $this
      ->api
      ->unlink('sale.order', [$order_id]);
  }

}
