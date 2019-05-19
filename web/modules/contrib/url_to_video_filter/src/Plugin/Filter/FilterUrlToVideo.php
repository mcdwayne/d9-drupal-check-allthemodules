<?php

namespace Drupal\url_to_video_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\url_to_video_filter\Service\UrlToVideoFilterServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert various video sharing website URLs to links.
 *
 * @Filter(
 *   id = "filter_url_to_video",
 *   title = @Translation("Convert URLs to embedded videos"),
 *   description = @Translation("Converts URLs for various video sites and converts them to embedded videos"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *      "youtube" = true,
 *      "youtube_webp_preview" = false,
 *      "autoload" = false,
 *      "vimeo" = true,
 *   }
 * )
 */
class FilterUrlToVideo extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The Url to Video Filter Service.
   *
   * @var \Drupal\url_to_video_filter\Service\UrlToVideoFilterServiceInterface
   */
  protected $urlToVideoFilterService;

  /**
   * Constructs a UrlToVideoFilter object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\url_to_video_filter\Service\UrlToVideoFilterServiceInterface $urlToVideoFilterService
   *   The Url to Video Filter Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UrlToVideoFilterServiceInterface $urlToVideoFilterService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->urlToVideoFilterService = $urlToVideoFilterService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_to_video_filter.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $youtube_found = FALSE;
    $vimeo_found = FALSE;

    // Process YouTube URLs.
    if ($this->settings['youtube']) {
      $filter = $this->urlToVideoFilterService->convertYouTubeUrls($text);
      $text = $filter['text'];
      $youtube_found = $filter['url_found'];
    }

    // Process Vimeo Urls.
    if ($this->settings['vimeo']) {
      $filter = $this->urlToVideoFilterService->convertVimeoUrls($text);
      $text = $filter['text'];
      $vimeo_found = $filter['url_found'];
    }

    $libraries = [];
    $result = new FilterProcessResult($text);
    if ($youtube_found) {
      $libraries[] = 'url_to_video_filter/youtube_embed';
    }

    if ($vimeo_found) {
      $libraries[] = 'url_to_video_filter/vimeo_embed';
    }

    $js_settings['urlToVideoFilter'] = [];

    if ($this->settings['youtube'] && $this->settings['youtube_webp_preview']) {
      $js_settings['urlToVideoFilter']['youtubeWebp'] = TRUE;
    }

    if ($this->settings['autoload']) {
      $js_settings['urlToVideoFilter']['autoload'] = TRUE;
    }

    $result->setAttachments([
      'drupalSettings' => $js_settings,
      'library' => $libraries,
    ]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Enable filtering for YouTube URLs.
    $form['youtube'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter for YouTube URLs'),
      '#default_value' => $this->settings['youtube'],
    ];

    $form['youtube_webp_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use webp image for YouTube preview'),
      '#description' => $this->t('Warning - not compatible with some browsers'),
      '#default_value' => $this->settings['youtube_webp_preview'],
      '#states' => [
        'visible' => [
          '#edit-filters-filter-url-to-video-settings-youtube' => ['checked' => TRUE],
          '#edit-filters-filter-url-to-video-settings-autoload' => ['checked' => FALSE],
        ],
      ],
    ];

    // Enable filtering for Vimeo URLs.
    $form['vimeo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter for Vimeo URLs'),
      '#default_value' => $this->settings['vimeo'],
    ];

    $form['autoload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoload players when page has loaded.'),
      '#default_value' => $this->settings['autoload'],
    ];

    return $form;
  }

}
