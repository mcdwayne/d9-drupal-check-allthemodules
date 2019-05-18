<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\Template\Attribute;
use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Class VideoAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class EmbedVideoAssetFormatter extends EmbedAssetFormatterBase {

  private $controls;
  private $autoplay;
  private $loop;
  private $width;
  private $height;

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $type, array $display_settings) {
    parent::__construct($url, $type, $display_settings);

    $defaults = [
      'loop' => FALSE,
      'autoplay' => FALSE,
      'controls' => TRUE,
      'width' => FALSE,
      'height' => FALSE,
    ];

    foreach ($defaults as $prop => $default) {
      $this->{$prop} = static::getVal($display_settings, $prop) ?: $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    $attributes = new Attribute([]);

    $this->width = $this->width > 100
      ? $this->width
      : '100%';

    $this->height = $this->height > 100
      ? $this->height
      : FALSE;

    $attributes->setAttribute('width', $this->width);

    if (is_numeric($this->height)) {
      $attributes->setAttribute('height', $this->height);
    }
    if ($this->controls) {
      $attributes->setAttribute('controls', '');
    }
    if ($this->autoplay) {
      $attributes->setAttribute('autoplay', '');
    }
    if ($this->loop) {
      $attributes->setAttribute('loop', '');
    }

    return [
      '#theme' => 'ib_dam_embed_playable_resource',
      '#resource_type' => 'video',
      '#attributes' => $attributes,
      '#title' => $this->title,
      '#url' => $this->url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    $settings = [];
    $settings += AssetFeatures::getPlayableSettings();
    $settings += AssetFeatures::getViewableSettings();
    return $settings + parent::settingsForm($asset);
  }

}
