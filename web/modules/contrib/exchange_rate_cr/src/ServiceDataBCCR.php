<?php

namespace Drupal\exchange_rate_cr;

use GuzzleHttp\Exception\RequestException;

/**
 * Class ServiceDataBCCR.
 *
 * @package Drupal\exchange_rate_cr
 */
class ServiceDataBCCR {

  /**
   * This method process the response from the Banco Central.
   *
   * @param string $indicator
   *   Indicator to get from Banco Central.
   * @param string $startDate
   *   First date to get the data.
   * @param string $endDate
   *   Second date to get the data.
   * @param string $name
   *   Name of who is using the service.
   * @param string $sublevels
   *   Indicator sub-levels to consult.
   *
   * @return array
   *   The array with the response processed from the bank.
   */
  public function getDataFromBancoCentralCR($indicator, $startDate, $endDate, $name, $sublevels) {

    $response = [
      'successful' => FALSE,
      'date' => $startDate,
      'value' => 0,
      'message' => 'At the moment there is no communication with the Bank.',
    ];

    // Url Banco Central De Costa Rica.
    $url = 'http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicosXML';

    // Create a HTTP client.
    $client = \Drupal::httpClient();

    try {
      // Set options for our HTTP request.
      $request = $client->request('GET', $url, [
        'query' => [
          'tcIndicador' => $indicator,
          'tcFechaInicio' => $startDate,
          'tcFechaFinal' => $endDate,
          'tcNombre' => $name,
          'tnSubNiveles' => $sublevels,
        ],
      ]);

      // If successful HTTP query.
      if ($request->getStatusCode() == 200) {

        // Loading XML from the String.
        $xml = simplexml_load_string($request->getBody());

        $response['value'] = $this->getIndicator($xml, $indicator);
        $response['successful'] = TRUE;
        $response['message'] = '';
      }
    }
    catch (RequestException $e) {
      $dataTempStored = $this->getSharedTempStore($indicator);

      if ($dataTempStored['successful']) {
        $response['value'] = $dataTempStored['value'];
        $response['date'] = $dataTempStored['date'];
        $response['successful'] = TRUE;
      }
    }

    return $response;
  }

  /**
   * This method process the xml and get the value of the indicator.
   *
   * When we get the value, we will save it in the temporal
   *  shared store, in this way, if the communication with the
   *  bank fails we are going to have the value to do the conversion.
   *
   * @param array $xml
   *   Data from the bank.
   * @param string $indicator
   *   Indicator to get from Banco Central.
   *
   * @return float|false
   *   Float( If we succeed extracting it from XML ), or false( If we don't ).
   */
  public function getIndicator($xml, $indicator) {

    // Default response.
    $numValor = FALSE;

    // Checking if the xml is valid.
    if ($xml !== FALSE) {

      // Parsing XML to Object.
      $xmlObject = new \SimpleXMLElement($xml);

      // Check if the Object parsed is valid.
      if ($xmlObject !== FALSE) {

        // Getting the value of the indicator.
        $value = $xmlObject->INGC011_CAT_INDICADORECONOMIC->NUM_VALOR;

        // Checking if the value is valid.
        if ($value) {
          $numValor = floatval($value);
          $values_shared_temp_store = [
            'date' => date("j/n/Y"),
            'value' => $numValor,
            'successful' => TRUE,
          ];
          // Is valid, it will be save in temporal store.
          $this->setSharedTempStore($values_shared_temp_store, $indicator);
        }
      }
    }
    return $numValor;
  }

  /**
   * This method convert the currency.
   *
   * @param string $from
   *   Currency from we want to convert.
   * @param float $amount
   *   Amount that we want to convert.
   *
   * @return float
   *   Amount Converted.
   */
  public function convertCurrecy($from, $amount) {

    // Variable to Store the conversion result.
    $result = 0;

    // Variables for method getDataFromBancoCentralCR.
    $startDate = date("j/n/Y");
    $endDate = date("j/n/Y");
    $name = "exchange_rate_cr";
    $sublevels = "N";

    // Buy Rate.
    $buyRate = $this->getDataFromBancoCentralCR('317', $startDate, $endDate, $name, $sublevels)['value'];

    // Sell Rate.
    $sellRate = $this->getDataFromBancoCentralCR('318', $startDate, $endDate, $name, $sublevels)['value'];

    switch ($from) {
      case 'CRC':
        $result = $amount / $sellRate;
        break;

      case 'USD':
        $result = $amount * $buyRate;
        break;
    }
    return $result;
  }

  /**
   * This method is to save variables in temporal store.
   *
   * @param array $values
   *   Values to save.
   * @param string $indicator
   *   Indicator to save.
   */
  public function setSharedTempStore($values, $indicator) {
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $shared_temp_store->set('exchange_rate_data_' . $indicator, $values);
  }

  /**
   * This method is to get the variables in temporal store.
   *
   * @param string $indicator
   *   Indicator to get.
   *
   * @return array
   *   Value and if it was successful getting.
   */
  public function getSharedTempStore($indicator) {

    $response = [
      'successful' => FALSE,
      'date' => NULL,
      'value' => 0,
    ];

    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $values = $shared_temp_store->get('exchange_rate_data_' . $indicator);

    if ($values != NULL) {
      $response['successful'] = TRUE;
      $response['value'] = $values['value'];
      $response['date'] = $values['date'];
    }
    return $response;
  }

  /**
   * This method is to delete the variable in temporal store.
   *
   * @param string $indicator
   *   Indicator to delete.
   */
  public function deleteShardTempStore($indicator) {
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $shared_temp_store->delete('exchange_rate_data_' . $indicator);
  }

}
