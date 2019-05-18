<?php

/**
 * @file
 * Contains \Drupal\file_shadowbox\Plugin\field\formatter\FileShadowboxFormatter.
 */

namespace Drupal\file_shadowbox\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_shadowbox' formatter.
 *
 * @FieldFormatter(
 *   id = "file_shadowbox",
 *   label = @Translation("Shadowbox"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileShadowboxFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'icon' => '',
      'image_style' => '',
      'image_link' => '',
      'gallery' => '',
      'compact' => '',
      'title' => '',
      'video_width' => '640',
      'video_height' => '360',
      'video_thumb' => '128',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $settings = $this->getSettings();

    $element['icon'] = array(
      '#title' => t('Icon'),
      '#type' => 'select',
      '#default_value' => $settings['icon'],
      '#empty_option' => t('None (thumbnail)'),
      '#options' => array('ico' => 'always show icons'),
    );

    $image_styles = image_style_options(FALSE);
    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['image_link'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $gallery_options = array(
      'page' => 'gallery page',
      'field' => 'gallery field page',
      'nid' => 'gallery entity',
      'field_nid' => 'gallery field entity',
    );
    $element['gallery'] = array(
      '#title' => t('gallery'),
      '#type' => 'select',
      '#default_value' => $settings['gallery'],
      '#empty_option' => t('None (individual)'),
      '#options' => $gallery_options,
    );

    $element['compact'] = array(
      '#title' => t('compact'),
      '#type' => 'checkbox',
      '#default_value' => $settings['compact'],
    );

    $title_options = array(
      'node'        => 'node title',
      'description' => 'file description',
    );
    $element['title'] = array(
      '#title' => t('caption'),
      '#type' => 'select',
      '#default_value' => $settings['title'],
      '#empty_option' => t('None'),
      '#options' => $title_options,
    );

    $element['video_width'] = array(
      '#title' => t('Video width'),
      '#type' => 'textfield',
      '#default_value' => $settings['video_width'],
      '#maxlength' => 4,
      '#size' => 4,
      '#field_suffix' => 'px',
    );

    $element['video_height'] = array(
      '#title' => t('Video height'),
      '#type' => 'textfield',
      '#default_value' => $settings['video_height'],
      '#maxlength' => 4,
      '#size' => 4,
      '#field_suffix' => 'px',
    );

    $element['video_thumb'] = array(
      '#title' => t('Video thumbnail size'),
      '#type' => 'textfield',
      '#default_value' => $settings['video_thumb'],
      '#maxlength' => 4,
      '#size' => 4,
      '#field_suffix' => 'px',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();
    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$settings['image_style']])) {
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$settings['image_style']]));
    }
    else {
      $summary[] = t('Original image');
    }

    if (isset($image_styles[$settings['image_link']])) {
      $summary[] = t('Linked to: @style', array('@style' => $image_styles[$settings['image_link']]));
    }
    else {
      $summary[] = t('Linked to: Original image');
    }

    $gallery_options = array(
      'page'      => 'gallery page',
      'field'     => 'gallery field page',
      'nid'       => 'gallery entity',
      'field_nid' => 'gallery field entity',
    );
    if (isset($gallery_options[$settings['gallery']])) {
      $summary[] = t('as @gallery', array('@gallery' => (isset($settings['compact']) && $settings['compact'] ? 'compact ' : '') . $gallery_options[$settings['gallery']]));
    }

    $title_options = array(
      'node'        => 'node title',
      'description' => 'file description',
    );
    if (isset($title_options[$settings['title']])) {
      $summary[] = t('with @title as caption', array('@title' => $title_options[$settings['title']]));
    }

    $summary[] = t('video width: @width px, video height: @height px', array('@width' => $settings['video_width'], '@height' => $settings['video_height']));
    $summary[] = t('video thumbnail size: @size px', array('@size' => $settings['video_thumb']));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $entity = $items->getEntity();
    $settings = $this->getSettings();
    $config = \Drupal::config('shadowbox.settings');

    $shadowbox_enabled_path = _shadowbox_activation() && $config->get('shadowbox_enabled');

    switch ($settings['gallery']) {
      case 'page':
        $gallery_id = 'gallery';
        break;

      case 'field':
        $gallery_id = $items->getName();
        break;

      case 'nid':
        $gallery_id = implode('-', array($entity->getEntityTypeId(), $entity->id()));
        break;

      case 'field_nid':
        $gallery_id = implode('-', array($entity->getEntityTypeId(), $entity->id(), $items->getName()));
        break;

      default:
        $gallery_id = '';
        break;
    }

    $rel_gallery = ($gallery_id != '') ? "shadowbox[$gallery_id]" : 'shadowbox';
    $width = $settings['video_width'];
    $height = $settings['video_height'];
    $compact = $settings['compact'];

    foreach ($items as $delta => $item) {
      if ($item->isDisplayed() && $item->entity) {
        switch ($settings['title']) {
          case 'node':
            $title = $items->getEntity()->label();
            break;

          case 'description':
            $title = $item->description;
            break;

          default:
            $title = '';
            break;
        }

        $autoplay = $config->get('shadowbox_autoplay_movies');

        switch ($item->entity->getMimeType()) {
          case 'video/youtube':
            $uri = $item->entity->getFileUri();
            $youtube_id = substr($uri, strrpos($uri, '/') + 1);
            $url = 'http://www.youtube.com/embed/' . $youtube_id;

            $querystring = array();
            if ($autoplay) {
              $querystring['autoplay'] = 1;
            }

            $youtube_quality = $config->get('shadowbox_youtube_quality');
            if ($youtube_quality != 'auto') {
              $querystring['vq'] = $youtube_quality;
            }

            $url = !empty($querystring) ? $url . '?' . http_build_query($querystring) : $url;
            $ico = 'youtube.png';

            // if (\Drupal::moduleHandler()->moduleExists('media_youtube')) {
            //   $path = file_stream_wrapper_get_instance_by_uri($uri)->getLocalThumbnailPath();
            //   $image_style = $display['settings']['image_style'];
            // }
            // else {
              $path = 'http://img.youtube.com/vi/' . $youtube_id . '/0.jpg';
              $image_style = '';
            // }

            $attributes = array('width' => $settings['video_thumb'] . 'px');
            $rel = $rel_gallery . '; width=' . $width . '; height=' . $height;
            break;

          case 'video/vimeo':
            // if (\Drupal::moduleHandler()->moduleExists('media_vimeo')) {
            //   $uri = $item->entity->getFileUri();
            //   $parts = file_stream_wrapper_get_instance_by_uri($uri)->get_parameters();
            //   $vimeo_id = intval($parts['v']);
            //   $url = 'http://player.vimeo.com/video/' . $vimeo_id;
            //   $path = file_stream_wrapper_get_instance_by_uri($uri)->getLocalThumbnailPath();
            //   $image_style = $settings['image_style'];
            // }
            // else {
              $url = 'http://player.vimeo.com/video/' . $item['filename'];
              $path = _get_vimeo_thumbnail($item->entity->getFilename());
              $image_style = '';
            // }

            $url = $autoplay ? $url . '?autoplay=1' : $url;
            $ico = 'vimeo.png';
            $attributes = array('width' => $settings['video_thumb'] . 'px');
            $rel = $rel_gallery . '; width=' . $width . '; height=' . $height;
            break;

          case 'video/quicktime':
            $url = file_create_url($item->entity->getFileUri());
            $ico = 'mov.png';
            $path = FILE_SHADOWBOX_ICOPATH . $ico;
            $image_style = '';
            $rel = $rel_gallery . '; width=' . $width . '; height=' . $height;
            break;

          case 'video/x-ms-wmv':
            $url = file_create_url($item->entity->getFileUri());
            $ico = 'wmv.png';
            $path = FILE_SHADOWBOX_ICOPATH . $ico;
            $image_style = '';
            $rel = $rel_gallery . '; width=' . $width . '; height=' . $height;
            break;

          case 'video/x-flv':
            $url = file_create_url($item->entity->getFileUri());
            $ico = 'flv.png';
            $path = FILE_SHADOWBOX_ICOPATH . $ico;
            $image_style = '';
            $rel = $rel_gallery . '; width=' . $width . '; height=' . $height;
            break;

          case 'application/x-shockwave-flash':
            $url = file_create_url($item->entity->getFileUri());
            $ico = 'swf.png';
            $path = FILE_SHADOWBOX_ICOPATH . $ico;
            $image_style = '';
            $rel = $rel_gallery . '; player=swf; width=' . $width . '; height=' . $height;
            break;

          case 'application/pdf':
            $url = file_create_url($item->entity->getFileUri());
            $ico = 'pdf.png';
            $path = FILE_SHADOWBOX_ICOPATH . $ico;
            $image_style = '';
            $rel = '';
            break;

          default:
            if (strstr($item->entity->getMimeType(), 'image/')) {
              $linked_style = $settings['image_link'];

              if ($linked_style) {
                $style = entity_load('image_style', $linked_style);
                $uri = $style->buildUrl($item->entity->getFileUri());
                if (!file_exists($uri)) {
                  $uri = $style->buildUrl($item->entity->getFileUri());
                }
              }
              else {
                $uri = $item->entity->getFileUri();
              }

              $url = file_create_url($uri);
              $ico = 'image.png';
              $path = $item->entity->getFileUri();
              $image_style = $settings['image_style'];
              $rel = $rel_gallery;

            }
            else {
              $url = file_create_url($item->entity->getFileUri());
              $ico = 'generic.png';
              $path = FILE_SHADOWBOX_ICOPATH . $ico;
              $image_style = '';
              $rel = '';
            }
            break;
        }

        $shadowbox_thumbnail = array(
          '#theme' => 'shadowbox_thumbnail',
          '#path' => $settings['icon'] === 'ico' ? FILE_SHADOWBOX_ICOPATH . $ico : $path,
          '#title' => $title,
          '#alt' => $title,
          '#attributes' => isset($attributes) ? $attributes : NULL,
        );

        $elements[$delta] = array(
          '#theme' => 'shadowbox_formatter',
          '#innerHTML' => ($delta == 0 || !$compact) ? $shadowbox_thumbnail : '',
          '#title' => $title,
          '#url' => $url,
          '#rel' => $rel,
          '#class' => ($gallery_id != '') ? "sb-image sb-gallery sb-gallery-$gallery_id" : 'sb-image sb-individual',
        );

        if ($shadowbox_enabled_path) {
          $elements[$delta]['#attached']['library'][] = 'shadowbox/shadowbox';
        }
      }
    }

    return $elements;
  }
}