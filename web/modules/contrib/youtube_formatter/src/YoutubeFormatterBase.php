<?php

/**
 * @file
 * Contains \Drupal\youtube_formatter\Plugin\field\formatter\YoutubeFormatter.
 */

namespace Drupal\youtube_formatter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for youtube formatter format plugins.
 */
class YoutubeFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a FormatterBase object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    $settings = $configuration['settings'];
    $label = $configuration['label'];
    $view_mode = $configuration['view_mode'];
    $field_definition = $configuration['field_definition'];
    $third_party_settings = $configuration['third_party_settings'];
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '560',
      'height' => '315',
      'autoplay' => FALSE,
      'enable_privacy' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'textfield',
      '#size' => 20,
      '#description' => t('Suggested resolutions: 560 x 315, 640 x 360, 853 x 480, 1280 x 720.'),
      '#default_value' => $this->getSetting('width'),
      '#element_validate' => ['element_validate_integer_positive'],
    ];
    $element['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('height'),
      '#element_validate' => ['element_validate_integer_positive'],
      '#required' => TRUE,
    ];
    $element['autoplay'] = [
      '#title' => $this->t('Auto play first Video'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $element['enable_privacy'] = [
      '#title' => $this->t('Enable privacy-enhanced mode'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('enable_privacy'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Video width: @width', ['@width' => $this->getSetting('width')]);
    $summary[] = t('Video height: @height', ['@height' => $this->getSetting('height')]);
    $summary[] = t('Autoplay: @autoplay', ['@autoplay' => $this->getSetting('autoplay')]);
    $summary[] = t('Privace mode: @enable_privacy', ['@enable_privacy' => $this->getSetting('enable_privacy')]);

    return $summary;
  }

  /**
   * Returns the video id related on the path format.
   */
  protected function getVideoId($item) {
    $video_id = explode('/', $item->value);
    // Return FALSE if the uri seams to be invalid.
    if (!isset($video_id[3])) {
      return FALSE;
    }
    else {
      $id = $video_id[3];
    }
    // Catch normal youtube links.
    $youtube = explode('.', $video_id[2]);
    if ($youtube[1] == 'youtube') {
      $params = explode('&amp;', $video_id[3]);
      $id = str_replace('watch?v=', '', $params[0]);
    }

    return $id;
  }

  /**
   * Returns the youtube url.
   */
  public function getYoutubeUri($item, $delta = 0) {
    $url = '';
    $domain = $this->getSetting('enable_privacy') ? 'www.youtube-nocookie.com' : 'www.youtube.com';

    $parameters = [
      'rel' => 'gallery-all',
      'wmode' => 'opaque',
    ];
    // Add autoplay parameter to first video.
    if ($delta == 0) {
      $parameters['autoplay'] = (int) $this->getSetting('autoplay');
    }

    $video_id = $this->getVideoId($item);

    // Check if id contains a playlist.
    if (count($playlist = explode('?', $video_id)) > 1) {
      $id = explode('=', $playlist[1]);
      $parameters['list'] = $id[1];
      $url = '//' . $domain . '/embed/videoseries';
    }
    else {
      $url = '//' . $domain . '/embed/' . $video_id;
    }

    return $url . '?' . http_build_query($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {}

}
