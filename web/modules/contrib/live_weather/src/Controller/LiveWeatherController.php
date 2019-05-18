<?php
/**
 * @file
 * Contains \Drupal\live_weather\Controller\LiveWeatherController.
 */

namespace Drupal\live_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Controller for Live weather.
 */
class LiveWeatherController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a location form object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   */
  public function __construct(FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory) {
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a list of locations.
   */
  public function locationList() {
    $rows = array();
    $location_list = $this->configFactory->get('live_weather.location')->get('location');
    $build['live_weather_form'] = $this->formBuilder
      ->getForm('Drupal\live_weather\Form\LiveWeatherForm');
    $header = array(t('Woeid'), t('Location'),
      array('data' => t('Operations'), 'colspan' => 2),
    );
    if (!empty($location_list)) {
      foreach ($location_list as $key => $value) {
        $data['woeid'] = $key;
        $data['location'] = Html::escape($value);
        $operations = array();
        $operations['delete'] = array(
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('live_weather.delete', ['woeid' => $key]),
        );
        $data['operations'] = array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $operations,
          ),
        );
        $rows[] = $data;
      }
    }
    $build['live_weather_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No locations available.'),
    );
    return $build;
  }

}
