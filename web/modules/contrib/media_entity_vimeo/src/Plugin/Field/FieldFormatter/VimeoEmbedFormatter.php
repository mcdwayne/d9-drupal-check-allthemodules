<?php

/**
 * @file
 */

namespace Drupal\media_entity_vimeo\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media_entity_vimeo\VimeoEmbedFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'vimeo_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "vimeo_embed",
 *   label = @Translation("Vimeo embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class VimeoEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $vimeo_url;

  const VIMEO_VIDEO_URL = "https://vimeo.com";

  /**
   * The vimeo fetcher.
   *
   * @var \Drupal\media_entity_vimeo\Plugin\MediaEntity\Type\VimeoEmbedFetcher
   */
  protected $fetcher;

  /**
   * Constructs a VimeoEmbedFormatter instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, array $third_party_settings, VimeoEmbedFetcher $fetcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fetcher = $fetcher;
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
      $container->get('media_entity_vimeo.vimeo_embed_fetcher')
    );
  }

  /**
   * @inheritDoc
   */
  public static function defaultSettings() {
    return array(
      'width' => '450px',
      'height' => '480px',
      'loop' => TRUE,
      'autoplay' => FALSE,
      'allowfullscreen' => TRUE,
    ) + parent::defaultSettings();
  }

  /**
   * @inheritDoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player.'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player. Suggested values: 450px for the visual type and 166px for classic.'),
    ];

    $elements['allowfullscreen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Full Screen'),
      '#default_value' => $this->getSetting('allowfullscreen'),
      '#description' => $this->t('Enable to allow full screen.'),
    ];

    $elements['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $this->getSetting('loop'),
      '#description' => $this->t('Enable to allow looping of video.'),
    ];

    $elements['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Play'),
      '#default_value' => $this->getSetting('autoplay'),
      '#description' => $this->t('Enable to allow auto play.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    if ($this->getSetting('width')) {
      $summary[] = $this->t('Width: @width', ['@width' => $this->getSetting('width')]);
    }
    if ($this->getSettings('height')) {
      $summary[] = $this->t('Height: @height', ['@height' => $this->getSetting('height')]);
    }

    $summary[] = $this->t('Fullscreen: @fullscreen', ['@fullscreen' => $settings['allowfullscreen'] ? $this->t('TRUE') : $this->t('FALSE')]);
    $summary[] = $this->t('Loop: @loop', ['@loop' => $settings['loop'] ? $this->t('TRUE') : $this->t('FALSE')]);
    $summary[] = $this->t('Autoplay: @autoplay', ['@autoplay' => $settings['autoplay'] ? $this->t('TRUE') : $this->t('FALSE')]);
    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if ($type = $media_entity->getType()) {

      /** @var MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($video_id = $type->getField($media_entity, 'video_id')) {

          $video_url = self::VIMEO_VIDEO_URL . '/' . $video_id;

          $data = $this->fetcher->fetchVimeoEmbed($video_url, $this->getSetting('autoplay'), $this->getSetting('loop'), $this->getSetting('allowfullscreen'));

          $element[$delta] = [
            '#theme' => 'media_vimeo_embed',
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
            '#loop' => $this->getSetting('loop'),
            '#allowfullscreen' => $data['fullscreen'],
            '#title' => $data['title'],
            '#url' => $data['embed_url'],
          ];
        }

      }
    }

    return $element;
  }

}
