<?php

namespace Drupal\scroll_to_element;

use Drupal\Core\Config\Config;

class SettingsHandler {

  /** @var \Drupal\Core\Config\Config */
  private $config;

  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * @param \Drupal\Core\Config\Config $config
   * @return array
   */
  public function getSelectors() {
    return $this->processSelectors(
      $this->splitStringByLine($this->config->get('selectors'))
    );
  }

  /**
   * @param $selectors
   * @return array
   */
  private function splitStringByLine($selectors) {
    return preg_split("/\\r\\n|\\r|\\n/", $selectors);
  }

  /**
   * @param $lines
   * @param $defaultOffset
   * @param $defaultDuration
   * @return array
   */
  private function processSelectors($lines) {
    $result = [];

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line) {
        $result[] = $this->convertLineToSelectorSettings($line);
      }
    }

    return $result;
  }

  /**
   * @param $value
   * @param $defaultValue
   * @return mixed
   */
  private function getValueOrDefaulIfNoValueIsGiven($value, $field) {
    if (is_null($value)) {
      return $this->config->get('default_' . $field);
    }
    return $value;
  }

  /**
   * @param $line
   * @param $defaultOffset
   * @param $defaultDuration
   * @return array
   */
  private function convertLineToSelectorSettings($line) {
    list($selector, $offset, $duration) = array_pad(
      explode('|', $line),
      3,
      NULL
    );
    $offset = $this->getValueOrDefaulIfNoValueIsGiven(
      $offset,
      'offset'
    );
    $duration = $this->getValueOrDefaulIfNoValueIsGiven(
      $duration,
      'duration'
    );
    return [
      'selector' => $selector,
      'offset' => (int) $offset,
      'duration' => (int) $duration,
    ];
  }

}
