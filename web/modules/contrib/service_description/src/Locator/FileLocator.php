<?php

namespace Drupal\service_description\Locator;

use Drupal\service_description\Handler\ServiceDescriptionHandlerInterface;
use Symfony\Component\Config\FileLocator as BaseFileLocator;

/**
 * Class FileLocator.
 *
 * @package service_description
 */
class FileLocator extends BaseFileLocator {

  protected $handler;
  /**
   * {@inheritdoc}
   */
  public function __construct(ServiceDescriptionHandlerInterface $handler, $paths = array()) {
    $this->handler = $handler;
    parent::__construct($paths);
  }

  /**
   * {@inheritdoc}
   */
  public function locate($provider_id, $currentPath = null, $first = true) {
    $descriptions = $this->handler->getDescriptions();
    if (isset($descriptions[$provider_id])) {
      return $descriptions[$provider_id];
    }
  }

}
