<?php

namespace Drupal\colorbox_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'colorbox_field_formatter' formatter for images.
 *
 * @FieldFormatter(
 *   id = "colorbox_field_formatter_image",
 *   label = @Translation("Colorbox FF"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ColorboxFieldFormatterImage extends ColorboxFieldFormatter {
  /**
   * @inheritdoc
   */
  public static function defaultSettings() {
    return [
      'image_style' => 'original',
    ] + parent::defaultSettings();
  }

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $image_styles['hide'] = t('Hide (do not display image)');
    $form['image_style'] = [
      '#title' => $this->t('Content image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style to use in the content.'),
    ];

    return parent::settingsForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function settingsSummary() {
    $image_style = $this->getSetting('image_style');
    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);
    if (isset($image_styles[$image_style])) {
      $style = $image_styles[$image_style];
    }
    elseif ($image_style == 'hide') {
      $style = $this->t('Hide');
    }
    else {
      $style = $this->t('Original image');
    }
    return [
      $this->t('Content image style: @style', ['@style' => $style]),
    ] + parent::settingsSummary();
  }

  /**
   * @inheritdoc
   */
  protected function viewValue(FieldItemInterface $item) {
    return $item->view(['settings' => ['image_style' => $this->getSetting('image_style')],]);
  }

}
