<?php

namespace Drupal\flashpoint_course_content\Plugin\flashpoint_settings;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointSettingsInterface;


/**
 * @FlashpointSettings(
 *   id = "video_js_content",
 *   label = @Translation("Video.js Content"),
 * )
 */
class VideoJSContent extends PluginBase implements FlashpointSettingsInterface
{
  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Settings for Video.js content');
  }

  /**
   * Provide form options for the settings form.
   * @return array
   *   Array of Form API form elements.
   */
  public static function getFormOptions() {
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
    return [
      'video_js_content' => [
        '#type' => 'details',
        '#title' => t('Video.js settings'),
        'flashpoint_course_content__video_js_content' => [
          '#type' => 'textfield',
          '#title' => t('Video.js Watch Percentage'),
          '#description' => 'The percentage of time needed for a video to be marked as watched.',
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.video_js_content'),
        ],
      ],
    ];
  }

}