<?php

namespace Drupal\stock_photo_search;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for the provider plugins.
 */
abstract class ProviderPluginBase extends PluginBase implements ProviderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The directory where images are stored.
   *
   * @var string
   */
  protected $imagesDirectory = 'public://stock_photo_search_images';

  /**
   * The ID of the image.
   *
   * @var string
   */
  protected $imageId;

  /**
   * The input that caused the embed provider to be selected.
   *
   * @var string
   */
  protected $input;

  /**
   * The value to search.
   *
   * @var string
   */
  protected $searchValue;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, array $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (static::isApplicable($configuration['input'])) {
      $this->input = $configuration['input'];
      $this->imageId = $this->getIdFromInput($configuration['input']);
    }
    elseif (static::isSearch($configuration['searchValue'])) {
      $this->searchValue = $configuration['searchValue'];
    }
    else {
      throw new \Exception('Tried to create a stock photo provider plugin with invalid input.');
    }

    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('http_client'));
  }

  /**
   * Get the ID of the image.
   *
   * @return string
   *   The image ID.
   */
  protected function getImageId() {
    return $this->imageId;
  }

  /**
   * Get the input which caused this plugin to be selected.
   *
   * @return string
   *   The raw input from the user.
   */
  protected function getInput() {
    return $this->input;
  }

  /**
   * Get the Search Value.
   *
   * @return string
   *   The search value.
   */
  protected function getSearchValue() {
    return $this->searchValue;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/\[(.*?)\]/', $input, $result);
    return isset($result[1]) ? $result[1] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    $id = static::getIdFromInput($input);

    return !empty($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function isSearch($token) {
    return !empty($token);
  }

  /**
   * {@inheritdoc}
   */
  public function renderImage($image_style, $link_url) {
    $output = [
      '#theme' => 'image',
      '#uri' => $this->getLocalImageUri(),
    ];

    if (!empty($image_style)) {
      $output['#theme'] = 'image_style';
      $output['#style_name'] = $image_style;
    }

    if ($link_url) {
      $output = [
        '#type' => 'link',
        '#title' => $output,
        '#url' => $link_url,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadImage() {
    $local_uri = $this->getLocalImageUri();
    if (!file_exists($local_uri)) {
      file_prepare_directory($this->imagesDirectory, FILE_CREATE_DIRECTORY);
      $urlImage = trim(substr($this->input, 0, strpos($this->input, '[')));
      $extImage = substr($urlImage, strrpos($urlImage, '.'), strlen($urlImage) - strrpos($urlImage, '.'));

      $local_uri = $this->imagesDirectory . '/' . $this->getImageId() . $extImage;
      try {
        $image = $this->httpClient->request('GET', $this->getRemoteImageUrl($urlImage));
        file_unmanaged_save_data((string) $image->getBody(), $local_uri);
      }
      catch (\Exception $e) {
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalImageUri() {
    $urlImage = $this->imagesDirectory . '/' . $this->getImageId();

    if (file_exists($urlImage . '.jpg')) {
      return $urlImage . '.jpg';
    }

    if (file_exists($urlImage . '.jpeg')) {
      return $urlImage . '.jpeg';
    }

    if (file_exists($urlImage . '.gif')) {
      return $urlImage . '.gif';
    }

    if (file_exists($urlImage . '.svg')) {
      return $urlImage . '.svg';
    }

    if (file_exists($urlImage . '.png')) {
      return $urlImage . '.png';
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('@provider Image (@id)', ['@provider' => $this->getPluginDefinition()['title'], '@id' => $this->getImageId()]);
  }

}
