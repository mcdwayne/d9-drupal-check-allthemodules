<?php
/**
 * Page list model.
 * @author appels
 */

namespace Drupal\adcoin_payments\Model;
use Drupal\adcoin_payments\Exception\DatabaseException;


class PageList {
  /**
   * Lists names of publicly accessable routes.
   * NOTE: Only lists the routes without parameters.
   *
   * @return array Associative array of route names.
   *               The values match their respective keys.
   * @return NULL  On failure.
   *
   * @throws DatabaseException
   */
  public static function fetchPublicRouteNames() {
    try {
      $result = \Drupal::database()->select('router', 'r')
        ->condition(db_or()
          ->condition('name', 'adcoin_payments.success')
          ->condition('name', 'adcoin_payments.failed')
          ->condition('pattern_outline', '/user/%', 'LIKE')
          ->condition('pattern_outline', '/node/%', 'LIKE')
        )
        ->condition('pattern_outline', '%'.db_like('%').'%', 'NOT LIKE') // exclude routes with parameters
        // ->condition('pattern_outline', '%'.db_like('%').'%'.db_like('%').'%', 'NOT LIKE') // ignore routes with multiple parameters
        ->fields('r', ['name'])
        ->execute();
    } catch (\Exception $e) {
      throw new DatabaseException('fetchPublicRouteNames failed: ' . $e->getMessage);
      return NULL;
    } finally {
      // Format array as: [<route name> => <route name>]
      $keys = array_keys($result->fetchAllKeyed());
      return array_combine($keys, $keys);
    }
  }
}