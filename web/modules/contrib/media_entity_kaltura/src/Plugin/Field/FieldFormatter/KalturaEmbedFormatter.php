<?php

namespace Drupal\media_entity_kaltura\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_kaltura\Plugin\MediaEntity\Type\Kaltura;

/**
 * Plugin implementation of the 'kaltura_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "kaltura_embed",
 *   label = @Translation("Kaltura Embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class KalturaEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '400',
      'height' => '285',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player. Suggested value: 400'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player. Suggested value: 285'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if (($type = $media_entity->getType()) && $type instanceof kaltura) {
      /** @var \Drupal\media_entity\MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        $entry_id = $type->getField($media_entity, 'entry_id');
        $partner_id = $type->getField($media_entity, 'partner_id');
        $ui_conf_id = $type->getField($media_entity, 'ui_conf_id');
        $flash_vars = $type->getField($media_entity, 'flash_vars');

        if (($entry_id || $flash_vars) && $partner_id && $ui_conf_id) {
          $element[$delta] = [
            '#theme' => 'media_kaltura_embed',
            '#partnerId' => $partner_id,
            '#uiConfId' => $ui_conf_id,
            '#entryId' => $entry_id,
            '#flashVars' => $flash_vars,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
          ];
        }
      }
    }

    return $element;
  }

}
