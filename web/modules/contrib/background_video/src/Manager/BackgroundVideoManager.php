<?php

namespace Drupal\background_video\Manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;

/**
 * Class BackgroundVideoManager
 *
 * @package Drupal\background_video\Manager.
 */
class BackgroundVideoManager {

  protected $config;

  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('config.background_video');
  }

  /**
   * Returns JS settings.
   * @return array
   */
  public function getJsSettings() {

    return [
      'mp4' => self::_background_video_geturl_preprocess_page('mp4'),
      'ogv' => self::_background_video_geturl_preprocess_page('ogv'),
      'webm' => self::_background_video_geturl_preprocess_page('webm'),
      'control_pos' => $this->config->get('background_video_control_position'),
      'loop' => $this->config->get('background_video_loop'),
      'auto_play' => $this->config->get('background_video_autoplay'),
      'video_id' => $this->config->get('background_video_id'),

    ];
  }

  public function _background_video_geturl_preprocess_page($type) {
    $fid = $this->config->get('background_video_source_' . $type)[0];
    if (!empty($fid)) {
      $file = File::load($fid);
      if ($file) {
         return file_create_url($file->toArray()['uri'][0]['value']);
      }
    }
  }

}
