<?php

namespace Drupal\layout_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Video' block.
 *
 * @Block(
 *   id = "layout_blocks_video",
 *   admin_label = @Translation("Video"),
 *   category = @Translation("Layout blocks")
 * )
 */
class VideoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'video_link' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['video_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video'),
      '#default_value' => $this->configuration['video_link'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['video_link'] = $form_state->getValue('video_link');
  }

  /**
   * Parse a link, and get some embed html back.
   */
  public static function getMarkupFromLink($link) {
    $provider = parse_url($link, PHP_URL_HOST);
    $video_markup = FALSE;
    if ($provider == 'vimeo.com' || $provider == 'www.vimeo.com') {
      $vimeo_id = parse_url($link, PHP_URL_PATH);
      $vimeo_id = str_replace('/', '', $vimeo_id);
      $video_markup = '<iframe src="https://player.vimeo.com/video/' . $vimeo_id . '?color=f20a0a&title=0&byline=0&portrait=0" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
    }
    elseif ($provider == 'www.youtube.com' || $provider == 'youtube.com') {
      parse_str(parse_url($link, PHP_URL_QUERY), $youtube_id);
      $video_markup = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $youtube_id['v'] . '?rel=0&amp;controls=1&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
    }
    elseif ($provider == 'youtu.be') {
      $youtube_id = substr($link, 17);
      $video_markup = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $youtube_id . '?rel=0&amp;controls=1&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
    }
    return $video_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $video_link = $this->configuration['video_link'];
    $video_markup = self::getMarkupFromLink($video_link);
    if (!$video_markup) {
      return [];
    }
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="layout-blocks-video-block">{{ video_markup | raw }}</div>',
      '#context' => [
        'video_markup' => $video_markup,
      ],
    ];
  }

}
