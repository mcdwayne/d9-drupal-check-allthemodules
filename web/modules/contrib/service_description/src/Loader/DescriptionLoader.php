<?php

namespace Drupal\service_description\Loader;

use Drupal\service_description\Locator\FileLocator;
use GuzzleHttp\Command\Guzzle\Description;

/**
 * Class DescriptionLoader.
 *
 * @package service_description
 */
class DescriptionLoader {

  /**
   * @var \Drupal\service_description\Locator\FileLocator
   */
  protected $fileLocator;

  /**
   * DescriptionLoader constructor.
   *
   * @param \Drupal\service_description\Locator\FileLocator $file_locator
   */
  public function __construct(FileLocator $file_locator) {
    $this->fileLocator = $file_locator;
  }


  /**
   * @param $provider_id
   *
   * @return \GuzzleHttp\Command\Guzzle\Description
   */
  public function load($provider_id) {
    return new Description($this->fileLocator->locate($provider_id));
  }

}
