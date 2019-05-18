<?php

/**
 * @file
 * Contains \Drupal\field_wistia\Plugin\Field\FieldFormatter\FieldWistiaFormatter.
 */

namespace Drupal\field_wistia\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_wistia_video' formatter.
 *
 * @FieldFormatter(
 *   id = "field_wistia_video",
 *   label = @Translation("Field Wistia formatter"),
 *   field_types = {
 *     "field_wistia"
 *   }
 * )
 */
class FieldWistiaFormatter extends FormatterBase
{

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
        // Implement default settings.
        'field_wistia_size' => '640x360',
        'field_wistia_width' => '',
        'field_wistia_height' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    return [
        // Implement settings form.
        'field_wistia_size' => [
          '#type' => 'select',
          '#title' => t('Field Wistia video size'),
          '#options' => field_wistia_size_options(),
          '#default_value' => $this->getSetting('field_wistia_size'),
        ],
        'field_wistia_width' => [
          '#type' => 'textfield',
          '#title' => t('Width'),
          '#size' => 10,
          '#default_value' => $this->getSetting('field_wistia_width'),
          '#states' => [
            'visible' => [
              ':input[name*="field_wistia_size"]' => array('value' => 'custom'),
            ],
          ],
        ],
        'field_wistia_height' => [
          '#type' => 'textfield',
          '#title' => t('Height'),
          '#size' => 10,
          '#default_value' => $this->getSetting('field_wistia_height'),
          '#states' => [
            'visible' => [
              ':input[name*="field_wistia_size"]' => array('value' => 'custom'),
            ],
          ],
        ],
        'field_wistia_embed_type' => [
          '#title' => t('Embed Type'),
          '#type' => 'select',
          '#options' => array(
            'iframe' => t('iFrame'),
            'async' => t('Async'),
            'async_popover' => t('Async Popover'),
          ),
          '#default_value' => $this->getFieldSetting('field_wistia_embed_type'),
        ],

      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];
    $cp = "";
    $field_wistia_size = $this->getSetting('field_wistia_size');
    // Implement settings summary.
    $parameters = [
      $this->getSetting('field_wistia_embed_type'),
    ] ;

    foreach ($parameters as $parameter) {
      if ($parameter) {
        $cp = t(', custom parameters');
        break;
      }
    }

    $summary[] = t('Wistia video: @field_wistia_size@cp', array('@field_wistia_size' => $field_wistia_size, '@cp' => $cp));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $element = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#theme' => 'field_wistia_video',
        '#url' => $item->input,
        '#video_id' => $item->video_id,
        '#entity_title' => $items->getEntity()->label(),
        '#settings' => $settings,
      );

    }

    return $element;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item)
  {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
