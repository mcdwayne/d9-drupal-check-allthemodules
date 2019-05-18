<?php

namespace Drupal\keycdn\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;
use Drupal\keycdn\Entity\KeyCDNPurgerSettings;
use GuzzleHttp\ClientInterface;

/**
 * Form for KeyCDN configurable purgers.
 */
class KeyCDNPurgerConfigForm extends PurgerConfigFormBase {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a \Drupal\purge_purger_http\Form\ConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \GuzzleHttp\ClientInterface $http_client
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->setConfigFactory($config_factory);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'keycdn.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = KeyCDNPurgerSettings::load($this->getId($form_state));
    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#description' => $this->t('A label that describes this purger.'),
      '#default_value' => $settings->name,
      '#required' => TRUE,
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the api key here.'),
      '#default_value' => $settings->api_key,
    ];
    $form['zone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zone'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the zone id here.'),
      '#default_value' => $settings->zone,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');
    $zone_id = $form_state->getValue('zone');
    $uri = 'https://api.keycdn.com/zones.json';

    // If api key and zone is set.
    if (!empty($api_key) && !empty($zone_id)) {
      try {
        $response = $this->httpClient->request('GET', $uri, [
          'auth' => [$api_key, ''],
        ]);

        // If key is not valid.
        if ($response->getStatusCode() != 200) {
          $form_state->setErrorByName('api_key', $this->t('Please check api key. It is either invalid or wrong.'));
        }

        // Validate the zone.
        $output = json_decode($response->getBody());
        if (empty($output->data->zones)) {
          $form_state->setErrorByName('zone', $this->t('Invalid zone id.'));
        }
        else {
          $valid_zone = FALSE;
          foreach ($output->data->zones as $zone) {
            // If zone id matches, no need to process the loop further.
            if ($zone->id == $zone_id) {
              $valid_zone = TRUE;
              break;
            }
          }

          // If not a valid zone.
          if (!$valid_zone) {
            $form_state->setErrorByName('zone', $this->t('Invalid zone id.'));
          }
        }
      }
      catch (\Exception $e) {
        $form_state->setErrorByName('api_key', $this->t('It looks like Key CDN is not responding or something is wrong.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    $settings = KeyCDNPurgerSettings::load($this->getId($form_state));

    // Iterate the config object and overwrite values found in the form state.
    foreach ($settings as $key => $default_value) {
      if (!is_null($value = $form_state->getValue($key))) {
        $settings->$key = $value;
      }
    }
    $settings->save();
  }
}
