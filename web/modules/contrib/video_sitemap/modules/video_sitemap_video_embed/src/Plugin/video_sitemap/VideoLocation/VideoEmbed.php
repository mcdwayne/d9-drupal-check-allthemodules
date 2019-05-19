<?php

namespace Drupal\video_sitemap_video_embed\Plugin\video_sitemap\VideoLocation;

use Drupal\media\MediaInterface;
use Drupal\video_embed_field\ProviderManagerInterface;
use Drupal\video_embed_field\ProviderPluginInterface;
use Drupal\video_sitemap\VideoLocationPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A VideoEmbed location plugin (used with video_embed_field module providers).
 *
 * @VideoLocation(
 *   id = "video_embed",
 *   title = @Translation("Video Embed (used with video_embed_field module providers)")
 * )
 */
class VideoEmbed extends VideoLocationPluginBase {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * Create a plugin with the given input.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video embed provider manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ProviderManagerInterface $provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('video_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnailLoc(MediaInterface $media) {
    $provider = $this->getProvider($media);
    if ($provider) {
      return $provider->getRemoteThumbnailUrl();
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayerLoc(MediaInterface $media) {
    $provider = $this->getProvider($media);
    switch ($provider->pluginId) {
      case 'youtube':
        return $this->getYoutubePlayerLoc($media, $provider);

      case 'vimeo':
        return $this->getVimeoPlayerLoc($media, $provider);

      default:
        return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLoc(MediaInterface $media) {
    return '';
  }

  /**
   * Get Media video provider.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return mixed
   *   Media video provider plugin.
   */
  private function getProvider(MediaInterface $media) {
    $provider = $this->providerManager->loadProviderFromInput($this->getSourceFieldValue($media));
    return isset($provider) ? $provider : NULL;
  }

  /**
   * Get Youtube video player URI.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   * @param \Drupal\video_embed_field\ProviderPluginInterface $provider
   *   Media video provider plugin.
   *
   * @return string
   *   Youtube video player URI.
   */
  protected function getYoutubePlayerLoc(MediaInterface $media, ProviderPluginInterface $provider) {
    if ($video_id = $provider->getIdFromInput($this->getSourceFieldValue($media))) {
      return sprintf('https://www.youtube.com/embed/', $video_id);
    }
    return '';
  }

  /**
   * Get Vimeo video player URI.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   * @param \Drupal\video_embed_field\ProviderPluginInterface $provider
   *   Media video provider plugin.
   *
   * @return string
   *   Vimeo video player URI.
   */
  protected function getVimeoPlayerLoc(MediaInterface $media, ProviderPluginInterface $provider) {
    if ($video_id = $provider->getIdFromInput($this->getSourceFieldValue($media))) {
      return sprintf('https://player.vimeo.com/video/%s', $video_id);
    }
    return '';
  }

  /**
   * Get the primary value stored in the Media source field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity..
   *
   * @return mixed
   *   The source value.
   *
   * @throws \RuntimeException
   *   If the source field for the media source is not defined.
   */
  protected function getSourceFieldValue(MediaInterface $media) {
    $source = $media->getSource();
    return $source->getSourceFieldValue($media);
  }

}
