<?php

namespace Drupal\bridtv\Plugin\MediaEntity\Type;

use Drupal\bridtv\BridResources;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Brid.TV Video type.
 *
 * @MediaType(
 *   id = "bridtv",
 *   label = @Translation("Brid.TV Video"),
 *   description = @Translation("This is a Brid.TV Video.")
 * )
 */
class Bridtv extends MediaTypeBase {

  /**
   * The brid resources service.
   *
   * @var \Drupal\bridtv\BridResources
   */
  protected $bridResources;

  /**
   * The Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A list of valid field names to get values for.
   *
   * @var array
   */
  static protected $validNames = [
    'video_id' => 'Brid.TV Video Id',
    'title' => 'Video title',
    'description' => 'Video description',
    'publish_date' => 'Publish date',
    'data' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setBridResources($container->get('bridtv.resources'));
    $instance->setHttpClient($container->get('http_client'));
    return $instance;
  }

  /**
   * Set the Brid.TV resources service.
   *
   * @param \Drupal\bridtv\BridResources $resources
   *   The Brid.TV resources service.
   */
  protected function setBridResources(BridResources $resources) {
    $this->bridResources = $resources;
  }

  /**
   * Set the Http client.
   *
   * @param \GuzzleHttp\Client $client
   *   The Http client.
   */
  protected function setHttpClient(Client $client) {
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    $fields = [];
    foreach (static::$validNames as $field => $label) {
      if ($label) {
        $fields[$field] = $this->t($label);
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    if (!empty($this->configuration['bridtv_field'])) {
      $data_field = $this->configuration['bridtv_field'];
      if ($media->hasField($data_field) && !$media->get($data_field)->isEmpty()) {
        if (isset(static::$validNames[$name])) {
          return $media->get($data_field)->first()->get($name)->getValue();
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if (!empty($this->configuration['local_images_uri']) && !empty($this->configuration['bridtv_field'])) {
      $data_field = $this->configuration['bridtv_field'];
      if ($media->hasField($data_field) && !$media->get($data_field)->isEmpty()) {
        $video_id = $this->getField($media, 'video_id');
        $local_file_uri = $this->configuration['local_images_uri'] . '/thumbnail/' . $video_id . '.jpg';
        if (file_exists($local_file_uri)) {
          return $local_file_uri;
        }

        if ($data = $media->get($data_field)->first()->getBridApiData()) {
          if (!empty($data['Video']['image'])) {
            $remote_image_uri = $data['Video']['image'];
            if (!strpos($remote_image_uri, '://') && !(strpos($remote_image_uri, '//') === 0)) {
              $remote_image_uri  = $this->bridResources->getSnaphotUrlFor($remote_image_uri);
            }
            $response = $this->httpClient->get($remote_image_uri);
            if ($response->getStatusCode() == 200) {
              $directory = $this->configuration['local_images_uri'] . '/thumbnail';
              if (!is_dir($directory)) {
                file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
              }
              file_unmanaged_save_data($response->getBody(), $local_file_uri, FILE_EXISTS_REPLACE);
            }
            if (file_exists($local_file_uri)) {
              return $local_file_uri;
            }
          }
        }
      }
    }
    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    if (!empty($this->configuration['bridtv_field'])) {
      $data_field = $this->configuration['bridtv_field'];
      if ($media->hasField($data_field) && !$media->get($data_field)->isEmpty()) {
        $title = $media->get($data_field)->first()->get('title')->getValue();
        if (!empty($title)) {
          return $title;
        }
      }
    }
    return parent::getDefaultName($media);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $options = [];
    $allowed_field_types = ['bridtv'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['bridtv_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Brid.TV video data field'),
      '#description' => $this->t('The field, which stores the information for a Brid.TV video, including any retrieved metadata.'),
      '#default_value' => empty($this->configuration['bridtv_field']) ? NULL : $this->configuration['bridtv_field'],
      '#options' => $options,
    ];

    $form['local_images_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Uri for locally stored images'),
      '#description' => $this->t('The uri location for storing local images. Leave empty to not use local images.'),
      '#default_value' => empty($this->configuration['local_images_uri']) ? NULL : $this->configuration['local_images_uri'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/video.png';
  }

}
