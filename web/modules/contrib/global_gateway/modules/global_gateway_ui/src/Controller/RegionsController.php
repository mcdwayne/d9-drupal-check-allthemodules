<?php

namespace Drupal\global_gateway_ui\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class RegionsController.
 *
 * @package Drupal\global_gateway_ui\Controller
 */
class RegionsController implements ContainerInjectionInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;
  /**
   * Redirect URL.
   *
   * @var \Drupal\Core\GeneratedUrl|string
   */
  protected $redirectUrl;

  /**
   * RegionsController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $factory) {
    $this->config = $factory->getEditable('global_gateway.disabled_regions');
    $this->redirectUrl = Url::fromRoute('global_gateway_ui.region_list')
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * Enable specific region.
   *
   * @param string $region_code
   *   Region code.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to list.
   */
  public function enable($region_code) {
    $disabled = $this->getDisabledList();
    if (in_array($region_code, $disabled)) {
      $key = array_search($region_code, $disabled);
      unset($disabled[$key]);
      $this->config->set('disabled', $disabled);
      $this->config->save(TRUE);
    }

    return new RedirectResponse($this->redirectUrl);
  }

  /**
   * Disable specific region.
   *
   * @param string $region_code
   *   Region code.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to list.
   */
  public function disable($region_code) {
    $disabled = $this->getDisabledList();
    if (!in_array($region_code, $disabled)) {
      $disabled[] = $region_code;
      $this->config->set('disabled', $disabled);
      $this->config->save(TRUE);
    }
    return new RedirectResponse($this->redirectUrl);
  }

  /**
   * Get list of disabled regions.
   *
   * @return array
   *   List of disabled regions.
   */
  private function getDisabledList() {
    $regions = $this->config->get('disabled');
    return !empty($regions) && is_array($regions)
      ? $regions
      : [];
  }

}
