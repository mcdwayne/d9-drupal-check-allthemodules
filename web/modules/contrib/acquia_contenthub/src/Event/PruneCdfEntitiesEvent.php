<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDFDocument;
use Symfony\Component\EventDispatcher\Event;

/**
 * Use to remove entities a subscriber does not want to import.
 *
 * This event has some technical ramifications and requires a deep
 * understanding of your dependencies. Removal of stand alone entities within
 * the CDF object is completely ok and normal with this process, but is seldom
 * necessary. If you are looking to replace an item slated for import with an
 * existing local entity, look at the EntityDataTamperEvent instead.
 */
class PruneCdfEntitiesEvent extends Event {

  /**
   * The CDF object.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject[]
   */
  protected $cdf;

  /**
   * PruneCdfEntitiesEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF document.
   */
  public function __construct(CDFDocument $cdf) {
    $this->cdf = $cdf;
  }

  /**
   * The CDF Objects to consider pruning.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   *   The CDF document.
   */
  public function getCdf() {
    return $this->cdf;
  }

  /**
   * The list of pruned CDF objects.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF document.
   */
  public function setCdf(CDFDocument $cdf) {
    $this->cdf = $cdf;
  }

}
