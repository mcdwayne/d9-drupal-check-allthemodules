<?php

namespace Drupal\vimeo_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'vimeo' formatter.
 *
 * @FieldFormatter(
 *   id = "vimeo_thumb",
 *   label = @Translation("Vimeo Thumbnail"),
 *   field_types = {
 *     "vimeo"
 *   }
 * )
 */
class VimeoThumbFormatter extends FormatterBase implements ContainerFactoryPluginInterface {


  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ClientInterface $http_client) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'vimeo_thumb_size' => 'small',
      'vimeo_target_blank' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $options = [
      'small' => '100px * 75px',
      'medium' => '200px * 150px',
      'large' => '640px * 360px',
    ];
    $elements['vimeo_thumb_size'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Vimeo Thumnail size'),
      '#default_value' => $this->getSetting('vimeo_thumb_size') ? $this->getSetting('vimeo_thumb_size') : "",
    ];
    $elements['vimeo_target_blank'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('_blank'),
      '#default_value' => $this->getSetting('vimeo_target_blank'),
      '#description' => $this->t('Opens the vimeo video in a new window or tab'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $vimeo_thumb_size = $this->getSetting('vimeo_thumb_size');
    $summary[] = $this->t('Vimeo Thumbnail Size (@vimeo_thumb_size).', [
      '@vimeo_thumb_size' => $vimeo_thumb_size,
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'vimeo_thumbnail',
        '#vimeoInfo' => $this->getVimeoVideoInfo($item->vimeo_url),
        '#target' => $this->getSetting('vimeo_target_blank'),
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getVimeoVideoInfo($url = '') {
    $thumbnail = [];
    $vimeo_thumb_size = $this->getSetting('vimeo_thumb_size');
    $id = vimeo_embed_field_get_vimeo_id_from_vimeo_url($url);
    if (($id['status'] == 1) && (!empty($id))) {
      $vimeo_video_id = $id['video_id'];
      $thumbnail = $this->getVimeoVideoThumbnailVideoId($vimeo_video_id, $vimeo_thumb_size);
    }
    return $thumbnail;
  }

  /**
   * Gets the thumbnail url for a vimeo video using the video id.
   *
   * @param string $id
   *   The video id.
   * @param string $thumbType
   *   Thumbnail image size. supported sizes: small, medium (default) and large.
   *
   * @return string
   *   vimeoInfo will return title, thumbnail and url of vimeo video.
   *   This only works for public videos.
   */
  public function getVimeoVideoThumbnailVideoId($id = '', $thumbType = '') {
    $id = trim($id);
    if ($id == '') {
      return FALSE;
    }
    try {
      $url = "https://vimeo.com/api/v2/video/$id.json";
      $response = $this->httpClient->request('GET', $url, ['headers' => ['Accept' => 'application/json']]);
      $getBody = $response->getBody();
      $data = json_decode($getBody, TRUE);
      if ((is_array($data)) && (count($data) > 0)) {
        $videoInfo = $data[0];
        $vimeoInfo['title'] = $videoInfo['title'];
        $vimeoInfo['url'] = $videoInfo['url'];
        switch ($thumbType) {
          case 'small':
            $vimeoInfo['thumbnail'] = $videoInfo['thumbnail_small'];
            return $vimeoInfo;

          case 'large':
            $vimeoInfo['thumbnail'] = $videoInfo['thumbnail_large'];
            return $vimeoInfo;

          case 'medium':
            $vimeoInfo['thumbnail'] = $videoInfo['thumbnail_medium'];
            return $vimeoInfo;

          default:
            break;
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('vimeo embed field', $e);
      return FALSE;
    }
  }

}
