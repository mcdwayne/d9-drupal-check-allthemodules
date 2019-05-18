<?php

namespace Drupal\image_hover_effects\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter as BaseImageFormatter;

/**
 * Plugin implementation of the 'image with hover effect' formatter.
 *
 * @FieldFormatter(
 *   id = "image_hover_effects_image",
 *   label = @Translation("Image with hover effect"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class ImageFormatter extends BaseImageFormatter {

  use FormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hover_effect' => '',
      'hover_text' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element += $this->buildSettingsForm($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array_merge(parent::settingsSummary(), $this->buildSettingsSummary());
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $this->updateElements($elements, $items->getEntity(), 'image_hover_effects_image_formatter');
    return $elements;
  }

}
