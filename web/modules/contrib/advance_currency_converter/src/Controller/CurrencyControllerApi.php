<?php

namespace Drupal\advance_currency_converter\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CurrencyControllerApi Doc Comment.
 *
 * @category class
 */
class CurrencyControllerApi extends ControllerBase {

  /**
   * It will store the connection of the database.
   *
   * @var databaseobject
   */
  protected $database;

  /**
   * The constructor will call and create an object of the database.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Creating database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * This function will help to achieve the DI using container interface.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Injecting a dependency.
   *
   * @return Drupal\Core\Database\Connection
   *   It will return the database class object.
   */
  public static function create(ContainerInterface $container) {

    return new static(
        $container->get('database')
    );
  }

  /**
   * This function will hit the api and get the data.
   *
   * @param mixed $from
   *   Currency from name.
   * @param mixed $to
   *   Currency to name.
   * @param mixed $amount
   *   Amount to conversion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json result will be returned.
   */
  public function apiHit($from, $to, $amount) {
    // This will get the information of the user selected currency.
    // The $to variable hold the information.
    $resi = $this->database->select('currency_offlne_data', 'c')
      ->fields('c', ['price'])
      ->condition('destination_currency', $to, '=')
      ->condition('date', date('Y-m-d'), '=')
      ->execute()->fetchAll();
    // Changing the data into the array format.
    $result = json_decode(json_encode($resi), TRUE);
    // This will get the information of the user selected from currency.
    // The $from variable hold the information.
    $res = $this->database->select('currency_offlne_data', 'c')
      ->fields('c', ['price'])
      ->condition('destination_currency', $from, '=')
      ->condition('date', date('Y-m-d'), '=')
      ->execute()->fetchAll();
    // Changing the data into the array format.
    $resultsecond = json_decode(json_encode($res), TRUE);
    // Returning the result of the changing currency into json format.
    return new JsonResponse(['Data' => ((1 / $resultsecond[0]['price']) * $result[0]['price']) * $amount]);
  }

}
