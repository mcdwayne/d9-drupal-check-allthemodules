<?php

namespace Drupal\ipma_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'IPMA Weather' Block.
 *
 * @Block(
 *   id = "ipma_weather",
 *   admin_label = @Translation("IPMA Weather"),
 * )
 */
class IpmaWeather extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache_backend, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cacheBackend = $cache_backend;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('ipma_weather.ipma_cache'), $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => 0,
      'weather_city' => 1110600,
      'weather_cache_time' => 2,
      'show_icon' => TRUE,
      'show_maxtemp' => TRUE,
      'show_mintemp' => TRUE,
      'show_description' => TRUE,
      'show_precipitation' => TRUE,
      'show_date' => TRUE,
      'show_source' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['weather'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('IPMA weather settings'),
    ];

    $form['weather']['weather_city'] = [
      '#type' => 'select',
      '#options' => self::getWeatherCities(),
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['weather_city'],
    ];

    $form['weather']['weather_cache_time'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Weather cache time'),
      '#description' => $this->t('Weather cache time in hours. Zero means no cache.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['weather_cache_time'],
    ];

    $form['weather']['show'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Block settings'),
    ];

    $form['weather']['show']['show_icon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Icon'),
      '#default_value' => $this->configuration['show_icon'],
    ];

    $form['weather']['show']['show_maxtemp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show maximum temperature'),
      '#default_value' => $this->configuration['show_maxtemp'],
    ];

    $form['weather']['show']['show_mintemp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show minimum temperature'),
      '#default_value' => $this->configuration['show_mintemp'],
    ];

    $form['weather']['show']['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show weather description'),
      '#default_value' => $this->configuration['show_description'],
    ];

    $form['weather']['show']['show_precipitation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show precipitation probability'),
      '#default_value' => $this->configuration['show_precipitation'],
    ];

    $form['weather']['show']['show_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show weather day'),
      '#default_value' => $this->configuration['show_date'],
    ];

    $form['weather']['show']['show_source'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show source'),
      '#default_value' => $this->configuration['show_source'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $block_values = $form_state->getValue('weather');
    $this->configuration['weather_city'] = $block_values['weather_city'];
    $this->configuration['weather_cache_time'] = $block_values['weather_cache_time'];
    $this->configuration['show_icon'] = $block_values['show']['show_icon'];
    $this->configuration['show_maxtemp'] = $block_values['show']['show_maxtemp'];
    $this->configuration['show_mintemp'] = $block_values['show']['show_mintemp'];
    $this->configuration['show_description'] = $block_values['show']['show_description'];
    $this->configuration['show_precipitation'] = $block_values['show']['show_precipitation'];
    $this->configuration['show_date'] = $block_values['show']['show_date'];
    $this->configuration['show_source'] = $block_values['show']['show_source'];

    Cache::invalidateTags($this->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ipma_weather',
      '#city' => self::getWeather(),
      '#attached' => [
        'library' => 'ipma_weather/drupal.weather',
      ],
      '#cache' => [
        'tags' => $this->getCacheTags(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(
        parent::getCacheTags(), ['ipma_weather:block']
    );
  }

  /**
   * Gets the list of avalilable locations from the API.
   */
  public function getWeatherCities() {
    $options = [];
    try {
      $request = $this->httpClient->get('http://api.ipma.pt/open-data/distrits-islands.json');
      $responseData = $request->getBody()->getContents();
      $data = Json::decode($responseData, TRUE);

      if (empty($data) || !is_array($data)) {
        drupal_set_message($this->t('Could not fetch weather information'), 'error');
      }
      else {
        foreach ($data['data'] as $city) {
          $options[$city['globalIdLocal']] = $city['local'];
        }
      }
    }
    catch (RequestException $e) {
      drupal_set_message($this->t('Could not fetch weather information; HTTP code: @code', ['@code' => $e->getCode()]), 'error');
    }

    return $options;
  }

  /**
   * Gets the build array from to send to the theme function. Handles cache.
   */
  public function getWeather() {
    $cache = $this->cacheBackend->get('ipma_weather_block');
    if ($cache && $this->configuration['weather_cache_time']) {
      $city = $cache->data;
    }
    else {
      try {
        $request = $this->httpClient->get('http://api.ipma.pt/open-data/forecast/meteorology/cities/daily/' . $this->configuration['weather_city'] . '.json');
        $responseData = $request->getBody()->getContents();
        $data = Json::decode($responseData, TRUE);

        if (empty($data) || !is_array($data)) {
          drupal_set_message($this->t('Could not fetch weather information'), 'error');
        }
        else {
          foreach ($data['data'] as $city_value) {
            $city = [
              'icon' => ($this->configuration['show_icon']) ? $city_value['idWeatherType'] : FALSE,
              'tmax' => ($this->configuration['show_maxtemp']) ? $city_value['tMax'] . '°' : FALSE,
              'tmin' => ($this->configuration['show_mintemp']) ? $city_value['tMin'] . '°' : FALSE,
              'desc' => ($this->configuration['show_description']) ? self::getDesc($city_value['idWeatherType']) : FALSE,
              'precip' => ($this->configuration['show_precipitation']) ? $this->t('@precip chance of rain', ['@precip' => $city_value['precipitaProb'] . '%']) : FALSE,
              'date' => ($this->configuration['show_date']) ? $city_value['forecastDate'] : FALSE,
              'source' => ($this->configuration['show_source']) ? TRUE : FALSE,
            ];
            break;
          }
        }
      }
      catch (RequestException $e) {
        drupal_set_message($this->t('Could not fetch weather information; HTTP code: @code', ['@code' => $e->getCode()]), 'error');
      }

      $this->cacheBackend->set('ipma_weather_block', $city, strtotime('+' . $this->configuration['weather_cache_time'] . ' hours'), $this->getCacheTags());
    }

    return $city;
  }

  /**
   * Gets the text description from the API to the selected type.
   */
  public function getDesc($id) {
    $cache = $this->cacheBackend->get('ipma_weather_descs');
    if ($cache) {
      $descs = $cache->data;
    }
    else {
      $descs = [];
      try {
        $request = $this->httpClient->get('http://api.ipma.pt/open-data/weather-type-classe.json');
        $responseData = $request->getBody()->getContents();
        $data = Json::decode($responseData, TRUE);

        if (empty($data) || !is_array($data)) {
          drupal_set_message($this->t('Could not fetch weather information'), 'error');
        }
        else {
          foreach ($data['data'] as $value) {
            $descs[$value['idWeatherType']] = $this->t($value['descIdWeatherTypeEN']);
          }
        }
      }
      catch (RequestException $e) {
        drupal_set_message($this->t('Could not fetch weather information; HTTP code: @code', ['@code' => $e->getCode()]), 'error');
      }

      $this->cacheBackend->set('ipma_weather_descs', $descs, strtotime('+24 hours'));
    }

    return !empty($descs[$id]) ? $descs[$id] : FALSE;
  }

}
