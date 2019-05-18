<?php

namespace Drupal\flickr_stream\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flickr_stream\FlickrStreamApi;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal;

/**
 * Plugin implementation of the 'FlickrStreamDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "FlickrStreamDefaultFormatter",
 *   label = @Translation("Flickr Stream"),
 *   field_types = {
 *     "FlickrStream"
 *   }
 * )
 */
class FlickrStreamDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The flickr api service.
   *
   * @var \Drupal\flickr_stream\FlickrStreamApi
   */
  protected $flickrApi;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FlickrStreamApi $flickrApi) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->flickrApi = $flickrApi;
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
      // Add Flickr Service.
      $container->get('flickr.stream.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'flickr_images_style' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $styles = ['default' => 'Default'];
    foreach (array_keys(ImageStyle::loadMultiple()) as $style) {
      $styles[$style] = ucfirst($style);
    }

    $element['flickr_images_style'] = [
      '#title' => t('Flickr image style'),
      '#type' => 'select',
      '#options' => $styles,
      '#default_value' => ($this->getSetting('flickr_images_style')) ?: 'default',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $summary[] = $this->t('Images style: @style', ['@style' => $settings['flickr_images_style']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $flickr_images = '';
    $image_style = $this->getSettings();
    foreach ($items as $delta => $item) {
      if (!empty($item->flickr_stream_user_id)) {
        $flickr_conf = $this->flickrApi->setConfig($item->flickr_stream_user_id, $item->flickr_stream_photoset_id, $item->flickr_stream_photo_count);
        if (!empty($item->flickr_stream_photoset_id)) {
          $flick_api_results = $this->flickrApi->getAlbumPhotos($flickr_conf);
          if (!empty($flick_api_results)) {
            $flickr_images = $this->flickrApi->flickrBuildImages($flick_api_results, 'album', $image_style);
          }
        }
        else {
          $flick_api_results = $this->flickrApi->getUserPhotos($flickr_conf);
          if (!empty($flick_api_results)) {
            $flickr_images = $this->flickrApi->flickrBuildImages($flick_api_results, 'user', $image_style);
          }
        }
        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => $flickr_images,
        ];
      }
      else {
        Drupal::logger('flickr_stream')->alert('Please check flickr fields inputs. User id empty');
      }
    }

    // Cached element for session.
    $elements['#cache'] = [
      'contexts' => ['session'],
      'tags' => [],
      'max-age' => Cache::PERMANENT,
    ];

    return $elements;
  }

}
