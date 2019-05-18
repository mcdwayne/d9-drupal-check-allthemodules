<?php

namespace Drupal\md_fontello\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @FieldType(
 *   id = "md_icon",
 *   label = @Translation("Fontello Icon"),
 *   description = @Translation("A field containing an icon."),
 *   default_widget = "md_icon",
 *   default_formatter = "md_icon"
 * )
 */

class MDIconItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'packages' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $element = array();
    $fonts = \Drupal::service('md_fontello')->getListFonts();

    $options = [];

    foreach ($fonts as $index => $font) {
      $options[$font['name']] = $font['title'];
    }

    $element['packages'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Fontello Icon Packages'),
      '#default_value' => $this->getSetting('packages'),
      '#description' => t('The icon packages that should be made available in this field. If no packages are selected, all will be made available.'),
      '#options' => $options,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
