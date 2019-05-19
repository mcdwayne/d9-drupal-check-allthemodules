<?php

namespace Drupal\xhprof\XHProfLib\Symbol;

/**
 * Class Symbol
 *
 * @package Drupal\xhprof\XHProfLib\Symbol
 */
class Symbol {

  /**
   * @var
   */
  private $parent;

  /**
   * @var
   */
  private $child;

  /**
   * @var
   */
  private $ct;

  /**
   * @var
   */
  private $wt;

  /**
   * @var
   */
  private $cpu;

  /**
   * @var
   */
  private $mu;

  /**
   * @var
   */
  private $pmu;

  /**
   * @param $parent_child
   * @param $ct
   * @param $wt
   * @param $cpu
   * @param $mu
   * @param $pmu
   */
  public function __construct($parent_child, $ct, $wt, $cpu = NULL, $mu = NULL, $pmu = NULL) {
    $this->ct = $ct;
    $this->wt = $wt;
    $this->cpu = $cpu;
    $this->mu = $mu;
    $this->pmu = $pmu;

    list($this->parent, $this->child) = $this->parseParentChild($parent_child);
  }

  /**
   * @return mixed
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * @return mixed
   */
  public function getChild() {
    return $this->child;
  }

  /**
   * @return mixed
   */
  public function getCpu() {
    return $this->cpu;
  }

  /**
   * @return mixed
   */
  public function getCt() {
    return $this->ct;
  }

  /**
   * @return mixed
   */
  public function getMu() {
    return $this->mu;
  }

  /**
   * @return mixed
   */
  public function getPmu() {
    return $this->pmu;
  }

  /**
   * @return mixed
   */
  public function getWt() {
    return $this->wt;
  }

  /**
   * @param $metric
   *
   * @return mixed
   */
  public function getMetric($metric) {
    if (isset($this->$metric)) {
      return $this->$metric;
    }
    else {
      return NULL;
    }
  }

  /**
   * @param $parent_child
   *
   * @return array
   */
  private function parseParentChild($parent_child) {
    $ret = explode("==>", $parent_child);

    // Return if both parent and child are set
    if (isset($ret[1])) {
      return $ret;
    }

    return array(NULL, $ret[0]);
  }

}
