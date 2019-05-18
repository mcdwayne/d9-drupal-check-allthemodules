<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\ClientCDFObject;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event dispatched to build the clientcdf.
 */
class BuildClientCdfEvent extends Event {

  /**
   * The CDF Object for which to create attributes.
   *
   * @var \Acquia\ContentHubClient\CDF\ClientCDFObject
   */
  protected $cdf;

  /**
   * CdfAttributesEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDF\ClientCDFObject $cdf
   *   The CDF object.
   */
  public function __construct(ClientCDFObject $cdf) {
    $this->cdf = $cdf;
  }

  /**
   * Get the CDF being created.
   *
   * @return \Acquia\ContentHubClient\CDF\ClientCDFObject
   *   The CDF object.
   */
  public function getCdf() {
    return $this->cdf;
  }
}
