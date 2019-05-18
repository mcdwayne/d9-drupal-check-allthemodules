<?php

namespace Drupal\inmail;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;

/**
 * Thin interface for the analyzer plugin manager.
 *
 * @ingroup analyzer
 */
interface AnalyzerManagerInterface extends DiscoveryInterface, FactoryInterface {
}
