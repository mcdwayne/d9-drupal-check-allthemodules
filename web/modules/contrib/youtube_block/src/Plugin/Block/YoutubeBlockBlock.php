<?php

namespace Drupal\youtube_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;

/**
 * Provides an Youtube block.
 *
 * @Block(
 *   id = "youtube_block_block",
 *   admin_label = @Translation("Youtube block"),
 *   category = @Translation("Social")
 * )
 */
class YoutubeBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a YoutubeBlockBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP client.
   * @param ConfigFactory $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Client $http_client, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settings() {
    return array(
      'width' => '',
      'height' => '',
      'count' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    //die();
    $form['count'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of images to display.'),
      '#default_value' => isset($this->configuration['count']) ? $this->configuration['count'] : 4,
    );

    $form['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Image width in pixels.'),
      '#default_value' => isset($this->configuration['width']) ? $this->configuration['width'] : '',
    );

    $form['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Image height in pixels.'),
      '#default_value' => isset($this->configuration['height']) ? $this->configuration['height'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    /*if (!is_numeric($form_state['values']['count'])) {
      form_set_error('count', $form_state, t('Count must be numeric'));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return;
    }
    else {
      $this->configuration['count'] = $form_state->getValue('count');
      $this->configuration['width'] = $form_state->getValue('width');
      $this->configuration['height'] = $form_state->getValue('height');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build a render array to return the Youtube Video Images.
    $build = array();
    $configuration = $this->configFactory->get('youtube_block.settings')->get();

    // If no configuration was saved, don't attempt to build block.
    if (empty($configuration['feed_url'])) {
      // @TODO Display a message instructing user to configure module.
      return $build;
    }

    // Build url for http request.
    $uri = $configuration['feed_url'];
    
    $url = Url::fromUri($uri)->toString();

    // Get the youtube images and decode.
    $result = $this->_fetchData($url);
    if (!$result) {
      return $build;
    }

    $json = json_encode($result);
    $data = json_decode($json,TRUE);


    $thumbnailxml = $result->xpath('//media:thumbnail');
    $xmlcount = 0;
    foreach ($data['entry'] as $media) {
         $build['children'][$media['id']] = array(
         '#theme' => 'youtube_block_image',
         '#data' => $media,
         '#href' => $media['link']['@attributes']['href'],
         '#src' => strval($thumbnailxml[$xmlcount]['url']),
         '#width' => $this->configuration['width'],
         '#height' => $this->configuration['height'],
       );
      $xmlcount++;
      if($xmlcount == $this->configuration['count'])
      {
        break;
      }
    }

    // Add css.
    if (!empty($build)) {
      $build['#attached']['library'][] = 'youtube_block/youtube_block';
    }

    return $build;
  }

  /**
   * Sends a http request to the Youtube API Server
   *
   * @param string $url
   *   URL for http request.
   *
   * @return bool|mixed
   *   The encoded response containing the youtube images or FALSE.
   */
  protected function _fetchData($url) {
    try {
      $response = $this->httpClient->get($url, array('headers' => array('Accept' => 'text/xml')));

      $xml =  simplexml_load_string($response->getBody(True));
      
      foreach($xml->entry as $entry) {
        $thumb [] = $entry->xpath('//media:thumbnail');
      }

      //$xml = simplexml_load_string($response->getBody(True));
      //$json = json_encode($xml);
      //$data = json_decode($json,TRUE);

      if (empty($xml)) {
        return FALSE;
      }

      //return $data;
      return $xml;
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

}
