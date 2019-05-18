<?php

namespace Drupal\extra_image_field_classes\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'extra_image_field_classes' formatter.
 *
 * @FieldFormatter(
 *   id = "extra_image_field_classes",
 *   label = @Translation("Extra Image Field Classes"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ExtraImageFieldClassesFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array('extra_image_field_classes' => '') + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['extra_image_field_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Extra Image Field Classes'),
      '#default_value' => $this->getSetting('extra_image_field_classes'),
      '#description' => t('Provide spaces for separating class name'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Extra classes for the Image Field: @class', array(
      '@class' => $this->getSetting('extra_image_field_classes'),
    ));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as &$element) {
      $element['#item_attributes']['class'][] = $this->getSetting('extra_image_field_classes');
    }

    return $elements;
  }

}
