<?php

namespace Drupal\packages\ParamConverter;

use Drupal\packages\PackagesInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Class PackageConverter.
 *
 * Parameter converter for package plugin Ids.
 *
 * @package Drupal\packages
 */
class PackageConverter implements ParamConverterInterface {

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Constructor.
   */
  public function __construct(PackagesInterface $packages) {
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return $this->packages->getPackage($value);
    }
    catch (PluginNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // TODO: Need anything else in here?
    return (!empty($definition['type']) && $definition['type'] == 'package');
  }

}
