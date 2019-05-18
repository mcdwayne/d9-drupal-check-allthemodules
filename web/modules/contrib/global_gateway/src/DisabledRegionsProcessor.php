<?php

namespace Drupal\global_gateway;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DisabledRegionsProcessor.
 *
 * @package Drupal\global_gateway
 */
class DisabledRegionsProcessor implements ContainerInjectionInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * All available regions array.
   *
   * @var array
   */
  protected $regions;
  /**
   * List of disabled regions.
   *
   * @var array
   */
  protected $disabledRegions;

  /**
   * DisabledRegionsProcessor constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $factory
   *   Config factory.
   * @param \Drupal\Core\Locale\CountryManagerInterface $manager
   *   Country manager.
   */
  public function __construct(
    ConfigFactoryInterface $factory,
    CountryManagerInterface $manager
  ) {
    $this->configFactory = $factory;
    $this->regions = $manager->getList();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('country_manager')
    );
  }

  /**
   * Get list of disabled regions.
   *
   * @return array
   *   Disabled regions list.
   */
  public function getDisabled() {
    if (!$this->disabledRegions) {
      $this->disabledRegions = $this->configFactory
        ->get('global_gateway.disabled_regions')
        ->get('disabled');
    }
    return $this->disabledRegions;
  }

  /**
   * Check whether the specified region is disabled or not.
   *
   * @param string $region_code
   *   Region code.
   *
   * @return bool
   *   Checking result.
   */
  public function isDisabled($region_code) {
    $disabled = $this->getDisabled();
    return in_array(strtolower($region_code), $disabled);
  }

  /**
   * Get list of regions excluding the disabled ones.
   *
   * @return array
   *   Regions list array.
   */
  public function getList() {
    foreach ($this->regions as $region_code => $region_name) {
      if ($this->isDisabled($region_code)) {
        unset($this->regions[$region_code]);
      }
    }
    return $this->regions;
  }

  /**
   * Helper method for removing disabled regions out of the regions list.
   *
   * @param array &$list
   *   Regions list to be filtered.
   */
  public function removeDisabledRegionsFromList(&$list) {
    if (!empty($list) && is_array($list)) {
      foreach ($list as $region_code => $region_name) {
        if ($this->isDisabled($region_code)) {
          unset($list[$region_code]);
        }
      }
    }
  }

  /**
   * Get fallback region code if the specified one is disabled.
   *
   * @param string $region_code
   *   Disabled region code.
   *
   * @return string|null
   *   Fallback region code. NULL if all regions are disabled.
   */
  public function getFallbackRegionCode($region_code) {
    $keys = array_keys($this->regions);
    $region_code = $keys[array_search($region_code, $keys) + 1];
    if ($this->isDisabled($region_code)) {
      return $this->getFallbackRegionCode($region_code);
    }
    return $region_code;
  }

}
