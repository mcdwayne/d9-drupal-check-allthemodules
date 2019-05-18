<?php

namespace Drupal\media_entity_panopto\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\media_entity_panopto\PanoptoMarkup;
use Drupal\media_entity_panopto\Plugin\MediaEntity\Type\Panopto;

/**
 * Plugin implementation of the 'panopto_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "panopto_embed",
 *   label = @Translation("Panopto embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class PanoptoEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '640',
      'height' => '480',
      'autoplay' => 'false',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element  = [];

    $element ['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Player Width'),
      '#description' => $this->t('The width of the player.'),
      '#default_value' => empty($this->getSetting('width')) ? NULL : $this->getSetting('width'),
    ];
    $element ['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Player Height'),
      '#description' => $this->t('The height of the player.'),
      '#default_value' => empty($this->getSetting('height')) ? NULL : $this->getSetting('height'),
    ];
    $element ['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#description' => $this->t('Play the video immediately.'),
      '#default_value' => empty($this->getSetting('autoplay')) ? NULL : $this->getSetting('autoplay'),
    ];

    return $element ;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    
    $media_entity = $items->getEntity();
    $width = $this->getSetting('width');
    $height = $this->getSetting('height');
    $autoplay = $this->getSetting('autoplay') == 1 ? '&autoplay=true' : '';

    $element = [];
    if (($type = $media_entity->getType()) && $type instanceof Panopto) {
      foreach ($items as $delta => $item) {
        $embed_url = $type->getField($media_entity, 'embed_url') . $autoplay;
        $markup = '<iframe src="' . $embed_url . '" width="' . $width . '" height="' . $height . '" frameborder="0" scrolling="no"></iframe>';
        $element[$delta] = [
          '#markup' => PanoptoMarkup::create($markup),
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'media';
  }

}
