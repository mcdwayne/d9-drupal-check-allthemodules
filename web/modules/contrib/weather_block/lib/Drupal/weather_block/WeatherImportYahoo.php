<?php

namespace Drupal\weather_block;
use Drupal\weather_block\WeatherImport;

class WeatherImportYahoo extends WeatherImport {

  function __construct($city, $units) {

    $this->location_id = $city;

    $this->temp = parent::fahrenheitToCelsius(20);

    $this->units = $units;

    $this->weather = $this->parseWeatherYahoo();
  }

  private function parseWeatherYahoo() {

    $xml = simplexml_load_file("http://weather.yahooapis.com/forecastrss?w=$this->location_id&u=" . $this->units);

    $currentday = array();

    foreach ($xml->channel as $item)
    {
      $namespaces = $item->getNameSpaces(true);
      $yt = $item->children($namespaces['yweather']);
      $weather = $yt->location->attributes();

      $currentday['city'] = (string)$weather['city'];

      $weather = $yt->wind->attributes();

      $currentday['wind']['speed'] = round((string)$weather['speed']);
      $currentday['wind']['direction'] = (string)$weather['direction'];

      $weather = $yt->atmosphere->attributes();

      $currentday['humidity'] = (string)$weather['humidity'];
    }

    foreach ($xml->channel->item as $item)
    {
      $namespaces = $item->getNameSpaces(true);
      $yt = $item->children($namespaces['yweather']);
      $weather = $yt->condition->attributes();

      $currentday['temp'] = (string)$weather['temp'];

      $currentday['condition']['text'] = (string)$weather['text'];

      $currentday['condition']['code'] = (string)$weather['code'];

      $i = 0;

      $forecast = array();

      while($i < count($yt->forecast)) {

        $weather = $yt->forecast[$i]->attributes();

        $key = $weather['day'] . " " . $weather['date'];

        $forecast[$key]['condition']['code'] = (string)$weather['code'];

        $forecast[$key]['condition']['text'] = (string)$weather['text'];

        $forecast[$key]['temp']['high'] = (string)$weather['high'];

        $forecast[$key]['temp']['low'] = (string)$weather['low'];

        $i++;
      }
    }

    $this->result = array('current' => $currentday, 'forecast' => $forecast);
  }
}
