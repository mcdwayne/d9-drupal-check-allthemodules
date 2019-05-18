<?php

namespace Drupal\flipping_book\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'flipping_book_iframe_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "flipping_book_iframe_formatter",
 *   label = @Translation("Flipping Book Iframe"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FlippingBookIframeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['width'] = '800px';
    $settings['height'] = '450px';
    $settings['class'] = 'flipping-book-iframe';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['width'] = [
      '#title' => $this->t('Iframe width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#title' => $this->t('Iframe height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
    ];

    $form['class'] = [
      '#title' => $this->t('Iframe CSS class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('class'),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      'width' => $this->t('Width: @width', ['@width' => $this->getSetting('width')]),
      'height' => $this->t('Height: @height', ['@height' => $this->getSetting('height')]),
      'class' => $this->t('CSS class: @class', ['@class' => $this->getSetting('class')]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->viewValue($item),
        '#allowed_tags' => ['iframe'],
      ];
    }

    return $elements;
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
  protected function viewValue(FieldItemInterface $item) {
    $value = $item->getValue();
    if (empty($value['target_id'])) {
      return NULL;
    }

    $flipping_book = $item->getRoot()->getValue();
    $settings = $this->getSettings();

    $url = \Drupal::service('flipping_book')
      ->buildFlippingBookUrl($flipping_book)
      ->toUriString();
    return '<iframe class="' . $settings['class'] . '" width="' . $settings['width'] . '" height="' . $settings['height'] . '" src="' . $url . '" frameborder="0" allowfullscreen></iframe>';
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return (
      parent::isApplicable($field_definition) &&
      ($field_definition->getTargetEntityTypeId() == 'flipping_book') &&
      ($field_definition->getName() == 'file')
    );
  }

}
