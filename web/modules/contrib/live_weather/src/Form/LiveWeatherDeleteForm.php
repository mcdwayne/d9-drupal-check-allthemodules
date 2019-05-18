<?php

/**
 * @file
 * Contains \Drupal\live_weather\Form\LiveWeatherDeleteForm.
 */

namespace Drupal\live_weather\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Builds the form to delete the location.
 */
class LiveWeatherDeleteForm extends ConfirmFormBase {

  private $woeid = NULL;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a location form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'live_weather_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete location?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('live_weather.location');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $woeid = NULL) {
    $this->woeid = $woeid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $locations = $this->configFactory->get('live_weather.location')->get('location');
    $woeid = $this->woeid;
    if (array_key_exists($woeid, $locations)) {
      unset($locations[$woeid]);
      $this->configFactory->getEditable('live_weather.location')
        ->set('location', $locations)
        ->save();
      $form_state->setRedirect('live_weather.location');
      drupal_set_message($this->t('Your @woeid - Where On Earth IDentification of location remvoed', array('@woeid' => $woeid)));
    }
    else {
      drupal_set_message($this->t('Your @woeid - Where On Earth IDentification of location is not valid', array('@woeid' => $woeid)));
    }
  }

}
