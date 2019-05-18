<?php
/**
 * @file
 * Contains \Drupal\httpbl\HostQuery.
 */

namespace Drupal\httpbl;

use Drupal\httpbl\Entity\Host;

abstract class HostQuery extends Host implements HostInterface {

  /**
   * {@inheritdoc}
   */
  public static function getHostsByIp($ip) {
    $query = \Drupal::entityQuery('host');
    $query->condition('host_ip', $ip);
    /** @var array $entity_ids */
    $entity_ids = $query->execute();
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  // @todo, @see Drupal\node\Plugin\views\argument\Nid.php (line 57) for example of how to use
  // the storage interface to handle loadMultiple (of title) to get nids.  It 
  // seems to correspond to finding hids by host_ip.
  public static function loadHostsByIp($ip) {
    $hosts = self::getHostsByIp($ip);
    /** @var array $hosts */
    $hosts = Host::loadMultiple($hosts);
    return $hosts;
  }

  /**
   * {@inheritdoc}
   */
  public static function countExpiredHosts($now) {
    $query = \Drupal::entityQuery('host');
    $query->condition('expire', $now, '<=');
    $count = $query->count()->execute();
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpiredHosts($now) {
    $query = \Drupal::entityQuery('host');
    $query->condition('expire', $now, '<=');
    $entity_ids = $query->execute();
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadExpiredHosts($now) {
    $hosts = self::getExpiredHosts($now);
    $hosts = Host::loadMultiple($hosts);
    return $hosts;
  }

}
