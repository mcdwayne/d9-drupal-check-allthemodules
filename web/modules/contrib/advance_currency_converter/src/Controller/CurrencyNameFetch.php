<?php

namespace Drupal\advance_currency_converter\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * CurrencyNameFetch Doc Comment.
 *
 * @category class
 */
class CurrencyNameFetch {

  protected $connection;
  protected $config;
  protected $http;

  /**
   * Building a form.
   *
   * {@inheritDoc}
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config, Client $httpClient) {
    $this->connection = $connection;
    $this->config = $config;
    $this->http = $httpClient;
  }

  /**
   * Collecting data from the google API.
   *
   * @param mixed $from_currency
   *   From Currency name.
   * @param mixed $to_currency
   *   To Currency name.
   * @param mixed $amount
   *   Amount want to convert.
   */
  public function collectingData($from_currency, $to_currency, $amount) {
    $url = 'https://finance.google.com/finance/converter?a=' . $amount . '&from=' . $from_currency . '&to=' . $to_currency;
    $client = $this->http->get($url, ['headers' => ['Accept' => 'text/plain']]);
    $data = (string) $client->getBody();
    return $data;
  }

  /**
   * It will fetch all the currency name and code.
   *
   * @return array
   *   It will return the currency name and code.
   */
  public function getInfo() {
    $result = $this->connection->select('curreny_converter', 'c')
      ->fields('c', ['CurrencyCode', 'CurrencyName'])
      ->execute()->fetchAll();
    $temp = json_decode(json_encode($result), TRUE);
    $arr = [];
    foreach ($temp as $val) {
      $arr[$val['CurrencyCode']] = $val['CurrencyName'];
    }
    return $arr;
  }

  /**
   * This will use in the currency convertor configuration settings.
   *
   * @param string $from_Currency
   *   From currency.
   * @param string $to_Currency
   *   To currency.
   * @param int $amount
   *   Amount conversion.
   *
   * @return stringint
   *   it will return string or int according to the conditions.
   */
  public function currencyApi($from_Currency, $to_Currency, $amount) {

    // It will check whether the currency configuration google api selected
    // or not.
    if ($this->config->get('currency.converter')->get('selection') == 'Google Currency Converter API' || $this->config->get('currency.converter')->get('selection') == NULL) {
      $data = $this->collectingData($from_Currency, $to_Currency, $amount);
      // It will match the content and save it into the differency variable.
      preg_match("/<span class=bld>(.*)<\/span>/", $data, $currencycheck);
      $result = explode(" ", $currencycheck[1]);
      return $result[0];
    }
    // It will check whether user selected to use the database currency checker.
    elseif ($this->config->get('currency.converter')->get('selection') == 'Data Offline Handling') {

      $res = $this->connection->select('currency_offlne_data', 'c')
        ->fields('c', ['price'])
        ->condition('destination_currency', $to_Currency, '=')
        ->condition('date', date('Y-m-d'), '=')
        ->execute()->fetchAll();
      $result = json_decode(json_encode($res), TRUE);
      $res = $this->connection->select('currency_offlne_data', 'c')
        ->fields('c', ['price'])
        ->condition('destination_currency', $from_Currency, '=')
        ->condition('date', date('Y-m-d'), '=')
        ->execute()->fetchAll();
      $resultsecond = json_decode(json_encode($res), TRUE);
      if (!empty($resultsecond) && !empty($result) && (int) $resultsecond[0]['price'] !== 0) {
        return ((1 / (int) $resultsecond[0]['price']) * $result[0]['price']) * $amount;
      }
    }
    // If the user did not select any thing from the currency convertor
    // configuration it will send the error.
    else {
      return 'Please Select the Currency Convertor API /admin/config/system/currency';
    }
  }

  /**
   * Check currencies.
   *
   * @return array
   *   Only selected currencies.
   */
  public function getCheck() {
    $options = $this->getInfo();
    $check = $this->config->get('currency.converter')->get('selecti');
    $arr = [];
    $ar = [];
    if ($check !== NULL) {
      foreach ($check as $key => $value) {
        if ($value != ' ') {
          $arr[$key] = $value;
        }
      }
      foreach ($arr as $key => $value) {
        foreach ($options as $keys => $value) {
          if ($key == $keys) {
            $ar[$key] = $value;
          }
        }
      }
    }
    return $ar;
  }

  /**
   * Graph Creation.
   *
   * @param string $from
   *   From Currency.
   * @param string $to
   *   To Currency.
   *
   * @return Json
   *   It will return json data of the currency trends.
   */
  public function createGraph($from, $to) {
    $day = $this->config->get('currency.converter')->get('days');
    // Getting the Price and date of the Source Currency.
    $fromarray = $this->connection->select('currency_offlne_data', 'cod')
      ->fields('cod', ['price', 'date'])
      ->condition('destination_currency', $from, '=')
      ->orderBy('cod.date', 'DESC')
      ->range(0, $day)
      ->execute()->fetchAll();
    $from_array_result = json_decode(json_encode($fromarray), TRUE);
    // Getting the Price and date of the destination currency.
    $toarray = $this->connection->select('currency_offlne_data', 'cod')
      ->fields('cod', ['price', 'date'])
      ->condition('destination_currency', $to, '=')
      ->orderBy('cod.date', 'DESC')
      ->range(0, $day)
      ->execute()->fetchAll();
    // Converting the data into the array.
    $to_array_result = json_decode(json_encode($toarray), TRUE);
    $newarray = [];
    $count = 0;
    // Creating a new array in the below steps.
    if (count($from_array_result) == 1) {
      for ($i = count($from_array_result) - 1; $i >= 0; $i--) {
        if ((int) $from_array_result[$i]['price'] !== 0) {
          $newarray[$count]['price'] = $to_array_result[$i]['price'] / (int) $from_array_result[$i]['price'];
          $newarray[$count]['date'] = date("d", strtotime($to_array_result[$i]['date']));
        }
        $count++;
      }
    }
    // Unsetting all the variable.
    unset($from_array_result);
    unset($toarray);
    unset($to_array_result);
    unset($fromarray);
    $new_json = json_encode($newarray);
    // Returning the json data to the FrontPanel file.
    return $new_json;
  }

}
