<?php

namespace Drupal\xhprof\XHProfLib;

use Drupal\xhprof\XHProfLib\Symbol\Symbol;

/**
 * Class Run
 */
class Run {

  /**
   * @var string
   */
  private $run_id;

  /**
   * @var string
   */
  private $namespace;

  /**
   * @var array
   */
  private $symbols = array();

  /**
   * @var \Drupal\xhprof\XHProfLib\Symbol\Symbol
   */
  private $mainSymbol;

  /**
   * @param string $run_id
   * @param string $namespace
   * @param array $data
   */
  public function __construct($run_id, $namespace, $data) {
    $this->run_id = $run_id;
    $this->namespace = $namespace;
    $this->symbols = $this->parseSymbols($data);
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->run_id;
  }

  /**+
   * @return array
   */
  public function getKeys() {
    return array_keys($this->symbols);
  }

  /**
   * @param string $key
   *
   * @return array
   */
  public function getMetrics($key) {
    return $this->symbols[$key];
  }

  /**
   * @return array
   */
  public function getSymbols() {
    return $this->symbols;
  }

  /**
   * @return Symbol
   */
  public function getMainSymbol() {
    return $this->mainSymbol;
  }

  /**
   * @return string
   */
  public function __toString() {
    return "Run id {$this->run_id}";
  }

  /**
   * @param $data
   *
   * @return array
   */
  private function parseSymbols($data) {
    $symbols = array();

    foreach ($data as $parent_child => $metrics) {

      if (!isset($metrics['cpu'])) {
        $metrics['cpu'] = NULL;
      }

      if (!isset($metrics['mu'])) {
        $metrics['mu'] = NULL;
      }

      if (!isset($metrics['pmu'])) {
        $metrics['pmu'] = NULL;
      }

      $symbol = new Symbol($parent_child, $metrics['ct'], $metrics['wt'], $metrics['cpu'], $metrics['mu'], $metrics['pmu']);
      $symbols[$parent_child] = $symbol;

      if ($symbol->getParent() == NULL) {
        $this->mainSymbol = $symbol;
      }
    }

    return $symbols;
  }

}
