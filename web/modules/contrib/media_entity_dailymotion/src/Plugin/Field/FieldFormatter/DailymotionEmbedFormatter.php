<?php

/**
 * @file
 */

namespace Drupal\media_entity_dailymotion\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'dailymotion_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "dailymotion_embed",
 *   label = @Translation("Dailymotion embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class DailymotionEmbedFormatter extends FormatterBase {


  protected $dailymotion_url;

  /**
   * @inheritDoc
   */
  public static function defaultSettings() {
    return array(
      'width' => '100%',
      'height' => '480px',
      'allowfullscreen' => TRUE,
      'allowautoplay' => FALSE,
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

    $elements['allowautoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Auto Play'),
      '#default_value' => $this->getSetting('allowautoplay'),
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
      $summary[] = $this->t('Height: @height px', ['@height' => $this->getSetting('height')]);
    }

    $summary[] = $this->t('Fullscreen: @fullscreen', ['@fullscreen' => $settings['allowfullscreen'] ? $this->t('TRUE') : $this->t('FALSE')]);
    $summary[] = $this->t('Autoplay: @autoplay', ['@autoplay' => $settings['allowautoplay'] ? $this->t('TRUE') : $this->t('FALSE')]);
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
          $embed_url = $type->getField($media_entity, 'embed_url');
          if ($this->getSetting('allowautoplay')) {
            $embed_url .= '?autoPlay=1';
          }

          $element[$delta] = [
            '#theme' => 'media_dailymotion_embed',
            '#video_id' => $video_id,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
            '#source' => $embed_url,
            '#allowfullscreen' => $this->getSetting('allowfullscreen') ? 'allowfullscreen' : '',
            '#allowautoplay' => $this->getSetting('allowautoplay'),
          ];
        }

      }
    }

    return $element;
  }

}
