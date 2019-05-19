<?php

/**
 * @file
 * Contains \Drupal\youtube_formatter\Plugin\field\formatter\YoutubeFormatterPreview.
 */

namespace Drupal\youtube_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\youtube_formatter\YoutubeFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'youtube formatter preview' formatter.
 *
 * @FieldFormatter(
 *   id = "youtube_formatter_preview",
 *   label = @Translation("Youtube formatter preview"),
 *   field_types = {
 *     "text",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class YoutubeFormatterPreview extends YoutubeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'image_link' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    $link_types = [
      'content' => $this->t('Linked to content'),
      'file' => $this->t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        // @todo Remove when theme_image_formatter() has support for route name.
        $uri['path'] = $entity->getSystemPath();
        $uri['options'] = $entity->urlInfo()->getOptions();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style_storgae = $this->entityManager->getStorage('image_style');
      $image_style = $image_style_storgae->load($image_style_setting);
      $cache_tags = $image_style->getCacheTag();
    }

    foreach ($items as $delta => $item) {
      if ($file = $this->getPreviewFile($item)) {
        $image_uri = $file->uri->value;
        if (isset($link_file)) {
          $uri = [
            'path' => file_create_url($image_uri),
            'options' => [],
          ];
        }
        $item->uri = $image_uri;

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[$delta] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $image_style_setting,
          '#path' => isset($uri) ? $uri : '',
          '#cache' => [
            'tags' => $cache_tags,
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * Helperfunction to register the youtube preview images in database for
   * remote_stream_wrapper.
   *
   * @param $item
   *   A field Item.
   * @return bool|mixed
   *   A file if exists.
   */
  private function getPreviewFile($item) {
    $id = $this->getVideoId($item);
    if ($id) {
      $directory = 'public://youtube_formatter';
      $destination = $directory . '/' . $id . '.jpg';
      $file = $this->loadFileByUri($destination);
      // Check weather the file already exists in the db.
      if (empty($file) && file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        $remote_url = 'http://img.youtube.com/vi/' . $id . '/0.jpg';
        $file = system_retrieve_file($remote_url, $destination, TRUE);
      }

      return $file;
    }

    return FALSE;
  }

  /**
   * Load a file object by URI.
   *
   * @param $uri
   *   An url string.
   * @return mixed $file
   *   A file object if exists.
   */
  private function loadFileByUri($uri) {
    $uri = file_stream_wrapper_uri_normalize($uri);
    $file_storage = $this->entityManager->getStorage('file');
    $files = $file_storage->loadByProperties(['uri' => $uri]);

    return reset($files);
  }

}
