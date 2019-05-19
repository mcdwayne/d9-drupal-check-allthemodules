<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauParser.
 */

namespace Drupal\weathercomau;

class WeathercomauParser implements WeathercomauParserInterface {

  /**
   * Weather.com.au RSS namespace.
   */
  const WCA_NAMESPACE = 'http://rss.weather.com.au/w.dtd';

  /**
   * {@inheritdoc}
   */
  public function parse($raw_xml) {
    try {
      $xml = new \SimpleXMLElement($raw_xml);

      $data = array(
        'current' => $this->parseCurrentConditions($xml),
        'forecast' => $this->parseForecast($xml),
        'link' => $this->parseLink($xml),
      );

      return $data;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Parses the `current conditions` data of the Weather.com.au XML.
   *
   * @param \SimpleXMLElement $xml
   *   A SimpleXMLElement object Weather.com.au data.
   *
   * @return array
   *   Array of parsed Weather.com.au `current conditions` data.
   */
  protected function parseCurrentConditions(\SimpleXMLElement $xml) {
    if (!isset($xml->channel->item[0])) {
      return array();
    }

    $item = $xml->channel->item[0];

    $data['title'] = (string) $item->title;
    $data['link'] = (string) $item->link;

    $current = $item->children(self::WCA_NAMESPACE)->current;
    foreach ($current->attributes() as $key => $value) {
      $data['data'][$key] = (string) $value;
    }

    return $data;
  }

  /**
   * Parses the `forecast` data of the Weather.com.au XML.
   *
   * @param \SimpleXMLElement $xml
   *   A SimpleXMLElement object Weather.com.au data.
   *
   * @return array
   *   Array of parsed Weather.com.au `forecast` data.
   */
  protected function parseForecast(\SimpleXMLElement $xml) {
    if (!isset($xml->channel->item[1])) {
      return array();
    }

    $item = $xml->channel->item[1];

    $data['title'] = (string) $item->title;
    $data['link'] = (string) $item->link;

    $forecasts = $xml->channel->item[1]->children(self::WCA_NAMESPACE)->forecast;
    foreach ($forecasts as $forecast) {
      $attributes = array();
      foreach ($forecast->attributes() as $key => $value) {
        $attributes[$key] = (string) $value;
      }
      $data['data'][] = $attributes;
    }

    return $data;
  }

  /**
   * Parses the `link` data of the Weather.com.au XML.
   *
   * @param \SimpleXMLElement $xml
   *   A SimpleXMLElement object Weather.com.au data.
   *
   * @return array
   *   Array of parsed Weather.com.au `link` data.
   */
  protected function parseLink(\SimpleXMLElement $xml) {
    return array(
      'title' => (string) $xml->channel->title,
      'url' => (string) $xml->channel->link,
    );
  }

}
