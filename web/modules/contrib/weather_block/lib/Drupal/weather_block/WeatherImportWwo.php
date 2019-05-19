<?php

namespace Drupal\weather_block;
use Drupal\weather_block\WeatherImport;

class WeatherImportWwo extends WeatherImport {

  function __construct($city, $units, $regkey) {

    $this->location_id = $city;

    $this->temp = parent::fahrenheitToCelsius(20);

    $this->units = $units;

    $this->regkey = $regkey;

    $this->weather = $this->parseWeatherWwo();
  }

  private function parseWeatherWwo() {

    $xml = simplexml_load_file("http://api.worldweatheronline.com/free/v1/weather.ashx?q=$this->location_id&format=xml&num_of_days=5&key=$this->regkey");

    $currentday = array();

    $currentday['city'] = (string)$xml->request->query;

    if ($this->units == 'c') {
      $currentday['temp'] = (string)$xml->current_condition->temp_F;
      $currentday['wind']['speed'] = (string)$xml->current_condition->windspeedMiles;
    }
    else {
      $currentday['temp'] = (string)$xml->current_condition->temp_C;
      $currentday['wind']['speed'] = (string)$xml->current_condition->windspeedKmph;
    }

    $currentday['wind']['direction'] = (string)$xml->current_condition->winddirDegree;

    $currentday['humidity'] = (string)$xml->current_condition->humidity;

    $cond = simplexml_load_file("http://www.worldweatheronline.com/feed/wwoConditionCodes.xml");

    $array = json_decode(json_encode($cond), TRUE);

    $key = $this->search((string)$xml->current_condition->weatherCode, $array['condition']);

    $currentday['condition']['text'] = $array['condition'][$key]['description'];

    $currentday['condition']['code'] = (string)$xml->current_condition->weatherCode;

    $forecast = array();

    foreach ($xml->weather as $item) {

      $key = t(date('D', strtotime($item->date))) . " " . $item->date;

      $forecast[$key]['condition']['code'] = (string)$item->weatherCode;

      $cond = simplexml_load_file("http://www.worldweatheronline.com/feed/wwoConditionCodes.xml");

      $array = json_decode(json_encode($cond), TRUE);

      $code = $this->search((string)$item->weatherCode, $array['condition']);

      $forecast[$key]['condition']['text'] = $array['condition'][$code]['description'];

      if ($this->units == 'c') {

        $forecast[$key]['temp']['high'] = (string)$item->tempMaxC;

        $forecast[$key]['temp']['low'] = (string)$item->tempMinC;
      }
      else {

        $forecast[$key]['temp']['high'] = (string)$item->tempMaxF;

        $forecast[$key]['temp']['low'] = (string)$item->tempMinF;
      }
    }

    $this->result = array('current' => $currentday, 'forecast' => $forecast);
  }

  private function search($needle, $haystack) {
    foreach ($haystack as $key=>$value) {

      $current_key=$key;

      if ($needle===$value OR (is_array($value) && $this->search($needle,$value))) {
        return $current_key;
      }
    }
    return false;
  }
}
