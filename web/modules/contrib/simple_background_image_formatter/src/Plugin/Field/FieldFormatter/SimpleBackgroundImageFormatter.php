<?php

namespace Drupal\simple_background_image_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_background_image",
 *   label = @Translation("Simple background-image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SimpleBackgroundImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'class' => 'background-image',
      'tag' => 'figure',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS class'),
      '#default_value' => $this->getSetting('class'),
    ];

    $form['tag'] = [
      '#type' => 'select',
      '#title' => $this->t('HTML tag'),
      '#description' => $this->t('The HTML tag to put this image on'),
      '#options' => ['div' => 'div', 'figure' => 'figure', 'span' => 'span'],
      '#default_value' => $this->getSetting('tag'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('CSS class: %class', ['%class' => $this->getSetting('class')]);
    $summary[] = $this->t('HTML tag: %tag', ['%tag' => $this->getSetting('tag')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as &$element) {
      $element['#theme'] = 'simple_background_image_formatter';
      $element['#attributes']['class'][] = $this->getSetting('class');
      $element['#tag'] = $this->getSetting('tag');
    }

    return $elements;
  }
}
