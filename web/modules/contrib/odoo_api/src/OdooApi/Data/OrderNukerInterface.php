<?php

namespace Drupal\odoo_api\OdooApi\Data;

/**
 * Order Nuker service interface.
 */
interface OrderNukerInterface {

  /**
   * Nuke the Odoo order.
   *
   * This method cancels all stock picking moves, pickings, and then cancels
   * and removes the order.
   *
   * @param int $order_id
   *   Odoo order ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\ClientException
   *   Odoo API client exceptions.
   */
  public function nuke($order_id);

}
