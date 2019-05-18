<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDFDocument;
use Drupal\depcalc\DependencyStack;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event dispatched to populate the DependencyStack without using CDF data.
 *
 * Often times, objects may exist locally after the initial import. Data Tamper
 * event subscribers can do a local lookup, and reference pre-existing content
 * on a subscribing site. Likewise they can build maps to sane defaults, or
 * event alter the content before it is imported.
 */
class EntityDataTamperEvent extends Event {

  /**
   * The CDF Array.
   *
   * @var \Acquia\ContentHubClient\CDFDocument
   */
  protected $cdf;

  /**
   * The dependency stack.
   *
   * @var \Drupal\depcalc\DependencyStack
   */
  protected $stack;

  /**
   * EntityDataTamperEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF array.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   */
  public function __construct(CDFDocument $cdf, DependencyStack $stack) {
    $this->cdf = $cdf;
    $this->stack = $stack;
  }

  /**
   * Get the current CDF document.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   *   The CDF document.
   */
  public function getCdf() {
    return $this->cdf;
  }

  /**
   * Set the CDF document.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF document.
   */
  public function setCdf(CDFDocument $cdf) {
    $this->cdf = $cdf;
  }

  /**
   * Get the dependency stack.
   *
   * @return \Drupal\depcalc\DependencyStack
   *   The dependency stack.
   */
  public function getStack() {
    return $this->stack;
  }

}
