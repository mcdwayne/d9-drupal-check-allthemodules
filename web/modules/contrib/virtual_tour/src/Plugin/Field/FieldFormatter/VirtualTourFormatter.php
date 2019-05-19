<?php

namespace Drupal\virtual_tour\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'virtual_tour' formatter.
 *
 * @FieldFormatter(
 *  id = "virtual_tour",
 *  label = @Translation("Virtual Tour"),
 *  field_types = {
 *     "image"
 *  },
 *  quickedit = {
 *    "editor" = "image"
 *  }
 * )
 */
class VirtualTourFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'virtual_tour_type' => '',
      'virtual_tour_display_style' => '',
      'virtual_tour_autoload' => '1',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
        $this->t('Configure Image Styles'),
        Url::fromRoute('entity.image_style.collection')
    );
    $element['virtual_tour_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Virtual tour type'),
      '#options' => $this->virtualTourEffectTypes(),
      '#default_value' => $this->getSetting('virtual_tour_type'),
    ];

    $element['virtual_tour_display_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => $image_styles,
      '#default_value' => $this->getSetting('virtual_tour_display_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#description' => $description_link->toRenderable(),
    ];

    $element['virtual_tour_autoload'] = [
      '#title' => $this->t('Autoload'),
      '#type' => 'checkbox',
      '#description' => $this->t('Select the Checkbox for autoload preview.'),
      '#default_value' => $this->getSetting('virtual_tour_autoload'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $image_styles = image_style_options(FALSE);
    $summary = [];
    unset($image_styles['']);
    $virtual_tour_type = $this->getSetting('virtual_tour_type');
    $image_display_setting = $this->getSetting('virtual_tour_display_style');
    $virtual_tour_effect_types = $this->virtualTourEffectTypes();
    if (isset($virtual_tour_effect_types[$virtual_tour_type])) {
      $summary[] = $this->t('Virtual Tour type: @style', ['@style' => $virtual_tour_effect_types[$virtual_tour_type]]);
      if (isset($image_styles[$image_display_setting])) {
        $summary[] = $this->t('Display image style: @style', ['@style' => $image_styles[$image_display_setting]]);
      }
      else {
        $summary[] = $this->t('Original image');
      }
    }
    else {
      $summary[] = $this->t('Configure Virtual Tour Effect Settings');
      $summary[] = $this->t('Effect type (Default)  : Equirectangular');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $display_style = $this->getSetting('virtual_tour_display_style');
    $virtual_tour_autoload = $this->getSetting('virtual_tour_autoload');

    // Settings array keep the value of virtual tour effect type.
    $settings = [
      'type' => $this->getSetting('virtual_tour_type'),
      'id' => 'panorama-',
    ];

    $element = [];
    $index = 0;
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'virtual_tour',
        '#item' => $item,
        '#display_style' => $display_style,
        '#settings' => $settings,
        '#virtual_tour_autoload' => $virtual_tour_autoload,
      ];
      $element['#attached']['drupalSettings']['virtual_tour'][$index++] = $settings;
    }
    return $element;
  }

  /**
   * Returns an array of available virtual tour effect types.
   */
  public function virtualTourEffectTypes() {
    $types = [
      'equirectangular' => $this->t('Equirectangular'),
    ];
    return $types;
  }

}
