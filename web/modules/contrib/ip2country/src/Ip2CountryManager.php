<?php

namespace Drupal\ip2country;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The ip2country.manager service.
 */
class Ip2CountryManager implements Ip2CountryManagerInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an Ip2CountryManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(RequestStack $requestStack, Connection $connection) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry($ip_address = NULL) {
    $ip_address = isset($ip_address) ? $ip_address : $this->currentRequest->getClientIp();

    $ipl = ip2long($ip_address);
    if (is_int($ip_address)) {
      $ipl = $ip_address;
    }

    // Locate IP within range.
    $sql = "SELECT country FROM {ip2country}
            WHERE (:start >= ip_range_first AND :end <= ip_range_last) LIMIT 1";
    $result = $this->connection->query($sql, [':start' => $ipl, ':end' => $ipl])->fetchField();
    return $result;
  }

}
