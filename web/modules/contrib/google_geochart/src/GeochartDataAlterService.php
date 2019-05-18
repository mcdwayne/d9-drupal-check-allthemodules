<?php

namespace Drupal\google_geochart;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class GeochartDataAlterService.
 */
class GeochartDataAlterService implements GeochartDataAlterServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GeochartDataAlterService object.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory->getEditable('google_geochart.geochartdefaultconfiguration');
  }

  /**
   * {@inheritdoc}
   */
  public function getGeochartData() {
    $data = $this->configFactory->get('default_google_visualization_arr');
    if (empty($data)) {
      $data = $this->configFactory->get('google_geochart')['default_google_visualization_arr'];
    }
    $data = json_decode($data);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setGeochartData($data) {
    $data_encoded = json_encode($data);
    $this->configFactory->set('default_google_visualization_arr', $data_encoded)->save();
  }

  /**
   * Get the api default maps api key.
   */
  public function getMapsApiKey() {
    return $this->configFactory->get('google_maps_api_key');
  }

}
